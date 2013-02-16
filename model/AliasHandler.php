<?php
# $Id$ 

/** 
 * Handlers User level alias actions - e.g. add alias, get aliases, update etc.
 * @property $username name of alias
 * @property $return return of methods
 */
class AliasHandler extends PFAHandler {

    protected $domain_field = 'domain';

    protected $called_by_MailboxHandler = false;
    
    /**
     *
     * @public
     */
    public $return = null;

    protected function initStruct() {
        $this->db_table = 'alias';
        $this->id_field = 'address';

        # hide 'goto_mailbox' if $this->new
        # (for existing aliases, init() hides it for non-mailbox aliases)
        $mbgoto = 1 - $this->new;

        $this->struct=array(
            # field name                allow       display in...   type    $PALANG label                     $PALANG description                 default / ...
            #                           editing?    form    list
            'address'       => pacol(   $this->new, 1,      1,      'mail', 'pEdit_alias_address'           , 'pCreate_alias_catchall_text'     ),
            'localpart'     => pacol(   $this->new, 0,      0,      'text', 'pEdit_alias_address'           , 'pCreate_alias_catchall_text'     , '', 
                /*options*/ '', 
                /*not_in_db*/ 1                         ),
            'domain'        => pacol(   $this->new, 0,      0,      'enum', ''                              , ''                                , '', 
                /*options*/ $this->allowed_domains      ),
            'goto'          => pacol(   1,          1,      1,      'txtl', 'pEdit_alias_goto'              , 'pEdit_alias_help'                ),
            'is_mailbox'    => pacol(   0,          0,      1,      'int', ''                             , ''                                , 0 ,
                # technically 'is_mailbox' is bool, but the automatic bool conversion breaks the query. Flagging it as int avoids this problem.
                # Maybe having a vbool type (without the automatic conversion) would be cleaner - we'll see if we need it.
                /*options*/ '',
                /*not_in_db*/ 0,
                /*dont_write_to_db*/ 1,
                /*select*/ 'coalesce(__is_mailbox,0) as is_mailbox',
                /*extrafrom*/ 'LEFT JOIN ( ' .
                    ' SELECT 1 as __is_mailbox, username as __mailbox_username ' .
                    ' FROM ' . table_by_key('mailbox') .
                    ' WHERE username IS NOT NULL ' .
                    ' ) AS __mailbox ON __mailbox_username = address' ),
            'goto_mailbox'  => pacol(   $mbgoto,    $mbgoto,$mbgoto,'bool', 'pEdit_alias_forward_and_store' , ''                                , 0,
                /*options*/ '',
                /*not_in_db*/ 1                         ), # read_from_db_postprocess() sets the value
            'on_vacation'   => pacol(   1,          0,      1,      'bool', 'pUsersMenu_vacation'           , ''                                , 0 ,
                /*options*/ '', 
                /*not_in_db*/ 1                         ), # read_from_db_postprocess() sets the value - TODO: read active flag from vacation table instead?
            'active'        => pacol(   1,          1,      1,      'bool', 'pAdminEdit_domain_active'      , ''                                , 1     ),
            'created'       => pacol(   0,          0,      1,      'ts',   'created'                       , ''                                ),
            'modified'      => pacol(   0,          0,      1,      'ts',   'pAdminList_domain_modified'    , ''                                ),
            'editable'      => pacol(   0,          0,      1,      'int', ''                             , ''                                , 0 ,
                # aliases listed in $CONF[default_aliases] are read-only for domain admins if $CONF[special_alias_control] is NO.
                # technically 'editable' is bool, but the automatic bool conversion breaks the query. Flagging it as int avoids this problem.
                # Maybe having a vbool type (without the automatic conversion) would be cleaner - we'll see if we need it.
                /*options*/ '',
                /*not_in_db*/ 0,
                /*dont_write_to_db*/ 1,
                /*select*/ '1 as editable'              ),
        );
    }

    protected function initMsg() {
        $this->msg['error_already_exists'] = 'pCreate_alias_address_text_error2';
        $this->msg['error_does_not_exist'] = 'pCreate_alias_address_text_error1'; # TODO: better error message
        if ($this->new) {
            $this->msg['logname'] = 'create_alias';
            $this->msg['store_error'] = 'pCreate_alias_result_error';
            $this->msg['successmessage'] = 'pCreate_alias_result_success';
        } else {
            $this->msg['logname'] = 'edit_alias';
            $this->msg['store_error'] = 'pEdit_alias_result_error';
            $this->msg['successmessage'] = 'pCreate_alias_result_success'; # TODO: better message for edit
        }
    }


    public function webformConfig() {
        if ($this->new) { # the webform will display a localpart field + domain dropdown on $new
            $this->struct['address']['display_in_form'] = 0;
            $this->struct['localpart']['display_in_form'] = 1;
            $this->struct['domain']['display_in_form'] = 1;
        }

        return array(
            # $PALANG labels
            'formtitle_create'  => 'pCreate_alias_welcome',
            'formtitle_edit'    => 'pEdit_alias_welcome',
            'create_button'     => 'pCreate_alias_button',

            # various settings
            'required_role' => 'admin',
            'listview'      => 'list-virtual.php',
            'early_init'    => 0,
            'prefill'       => array('domain'),
        );
    }

    /**
     * set a special flag if called by MailboxHandler
     */
    public function MailboxAliasConfig() {
        $this->called_by_MailboxHandler = true;
    }

    /**
     * AliasHandler needs some special handling in init() and therefore overloads the function.
     * It also calls parent::init()
     */
    public function init($id) {
        @list($local_part,$domain) = explode ('@', $id); # supress error message if $id doesn't contain '@'

        if ($local_part == '*') { # catchall - postfix expects '@domain', not '*@domain'
            $id = '@' . $domain;
        }

        $retval = parent::init($id);

        # hide 'goto_mailbox' for non-mailbox aliases
        # parent::init called view() before, so we can rely on having $this->return filled
        # (only validate_new_id() is called from parent::init and could in theory change $this->return)
        if ($this->new || $this->return['is_mailbox'] == 0) {
            $this->struct['goto_mailbox']['editable']        = 0;
            $this->struct['goto_mailbox']['display_in_form'] = 0;
            $this->struct['goto_mailbox']['display_in_list'] = 0;
        }

        if ( !$this->new && $this->return['is_mailbox'] && $this->admin_username != ''&& !authentication_has_role('global-admin') ) {
            # domain admins are not allowed to change mailbox alias $CONF['alias_control_admin'] = NO
            if (!boolconf('alias_control_admin')) {
                # TODO: make translateable
                $this->errormsg[] = "Domain administrators do not have the ability to edit user's aliases (check config.inc.php - alias_control_admin)";
                return false;
            }
        }

        return $retval;
    }

    protected function validate_new_id() {
        if ($this->id == '') {
            $this->errormsg[] = Lang::read('pCreate_alias_address_text_error1');
            return false;
        }

        list($local_part,$domain) = explode ('@', $this->id);

        if(!$this->create_allowed($domain)) {
            $this->errormsg[] = Lang::read('pCreate_alias_address_text_error3');
            return false;
        }
 
        # TODO: already checked in set() - does it make sense to check it here also? Only advantage: it's an early check
#        if (!in_array($domain, $this->allowed_domains)) { 
#            $this->errormsg[] = Lang::read('pCreate_alias_address_text_error1');
#            return false;
#        }

        if ($local_part == '') { # catchall
            $valid = true;
        } else {
            $valid = check_email($this->id); # TODO: check_email should return error message instead of using flash_error itsself
        }

        return $valid;
    }

    /**
     * check number of existing aliases for this domain - is one more allowed?
     */
    private function create_allowed($domain) {
        if ($this->called_by_MailboxHandler) return true; # always allow creating an alias for a mailbox

        $limit = get_domain_properties ($domain);

        if ($limit['aliases'] == 0) return true; # unlimited
        if ($limit['aliases'] < 0) return false; # disabled
        if ($limit['alias_count'] >= $limit['aliases']) return false;
        return true;
    }


   /**
    * merge localpart and domain to address
    * called by edit.php (if id_field is editable and hidden in editform) _before_ ->init
    */
    public function mergeId($values) {
        if ($this->struct['localpart']['display_in_form'] == 1 && $this->struct['domain']['display_in_form']) { # webform mode - combine to 'address' field
            if (empty($values['localpart']) || empty($values['domain']) ) { # localpart or domain not set
                return "";
            }
            if ($values['localpart'] == '*') $values['localpart'] = ''; # catchall
            return $values['localpart'] . '@' . $values['domain'];
        } else {
            return $values[$this->id_field];
        }
    }

    protected function setmore($values) {
        if ($this->new) {
            if ($this->struct['address']['display_in_form'] == 1) { # default mode - split off 'domain' field from 'address' # TODO: do this unconditional?
                list(/*NULL*/,$domain) = explode('@', $values['address']);
                $this->values['domain'] = $domain;
            }
        }

        if (! $this->new) { # edit mode - preserve vacation and mailbox alias if they were included before
            $old_ah = new AliasHandler();

            if (!$old_ah->init($this->id)) {
                $this->errormsg[] = $old_ah->errormsg[0];
            } elseif (!$old_ah->view()) {
                $this->errormsg[] = $old_ah->errormsg[0];
            } else {
                $oldvalues = $old_ah->result();

                if (!isset($values['on_vacation'])) { # no new value given?
                    $values['on_vacation'] = $oldvalues['on_vacation'];
                }

                if ($values['on_vacation']) { 
                    $values['goto'][] = $this->getVacationAlias();
                }

                if ($oldvalues['is_mailbox']) { # alias belongs to a mailbox - add/keep mailbox to/in goto
                    if (!isset($values['goto_mailbox'])) { # no new value given?
                        $values['goto_mailbox'] = $oldvalues['goto_mailbox'];
                    }
                    if ($values['goto_mailbox']) {
                        $values['goto'][] = $this->id;

                        # if the alias points to the mailbox, don't display the "empty goto" error message
                        if (isset($this->errormsg['goto']) && $this->errormsg['goto'] == Lang::read('pEdit_alias_goto_text_error1') ) {
                            unset($this->errormsg['goto']);
                        }
                    }
                }
            }
        }

        $this->values['goto'] = join(',', $values['goto']);
    }

    protected function storemore() {
        # TODO: if alias belongs to a mailbox, update mailbox active status
        return true;
    }

    protected function read_from_db_postprocess($db_result) {
        foreach ($db_result as $key => $value) {
            # split comma-separated 'goto' into an array
            $db_result[$key]['goto'] = explode(',', $db_result[$key]['goto']);

            # Vacation enabled?
            list($db_result[$key]['on_vacation'], $db_result[$key]['goto']) = remove_from_array($db_result[$key]['goto'], $this->getVacationAlias() );

            # if it is a mailbox, does the alias point to the mailbox?
            if ($db_result[$key]['is_mailbox']) {
                # this intentionally does not match mailbox targets with recipient delimiter.
                # if it would, we would have to make goto_mailbox a text instead of a bool (which would annoy 99% of the users)
                list($db_result[$key]['goto_mailbox'], $db_result[$key]['goto']) = remove_from_array($db_result[$key]['goto'], $key);
            } else { # not a mailbox
                $db_result[$key]['goto_mailbox'] = 0;
            }

            # TODO: set 'editable' to 0 if not superadmin, $CONF[special_alias_control] == NO and alias is in $CONF[default_aliases]
            # TODO: see check_alias_owner() in functions.inc.php
        }

        return $db_result;
    }

    public function getList($condition, $limit=-1, $offset=-1) {
        # only list aliases that do not belong to mailboxes
        return parent::getList( "__is_mailbox IS NULL AND ( $condition )", $limit, $offset);
    }

/* delete is already implemented in the "old functions" section
    public function delete() {
        $this->errormsg[] = '*** Alias domain deletion not implemented yet ***';
        return false; # XXX function aborts here until TODO below is implemented! XXX
        # TODO: move the needed code from delete.php here
    }
*/

    protected function _field_goto($field, $val) {
        if (count($val) == 0) {
            # empty is ok for mailboxes - this is checked in setmore() which can clear the error message
            $this->errormsg[$field] = Lang::read('pEdit_alias_goto_text_error1');
            return false;
        }

        $errors = array();

        foreach ($val as $singlegoto) {
            if (substr($singlegoto, 0, 1) == '@') { # domain-wide forward - check only the domain part
                # Note: alias domains are better, but we should keep this way supported for backward compatibility
                #       and because alias domains can't forward to external domains
                # TODO: allow this only if $this->id is a catchall?
                list (/*NULL*/, $domain) = explode('@', $singlegoto);
                if (!check_domain($domain)) {
                     $errors[] = "invalid: $singlegoto"; # TODO: better error message
                }
            } elseif (!check_email($singlegoto)) {
                $errors[] = "invalid: $singlegoto"; # TODO: better error message
            }
        }

        if (count($errors)) {
            $this->errormsg[$field] = join("   ", $errors); # TODO: find a way to display multiple error messages per field
            return false;
        } else {
            return true;
        }
    }

    /**
     * on $this->new, set localpart based on address
     */
    protected function _missing_localpart  ($field) {
        if (isset($this->RAWvalues['address'])) {
            $parts = explode('@', $this->RAWvalues['address']);
            if (count($parts) == 2) $this->RAWvalues['localpart'] = $parts[0];
        }
    }

    /**
     * on $this->new, set domain based on address
     */
    protected function _missing_domain     ($field) {
        if (isset($this->RAWvalues['address'])) {
            $parts = explode('@', $this->RAWvalues['address']);
            if (count($parts) == 2) $this->RAWvalues['domain'] = $parts[1];
        }
    }


     /**
     * Returns the vacation alias for this user. 
     * i.e. if this user's username was roger@example.com, and the autoreply domain was set to
     * autoreply.fish.net in config.inc.php we'd return roger#example.com@autoreply.fish.net
     * @return string an email alias.
     */
    protected function getVacationAlias() {
        $vacation_goto = str_replace('@', '#', $this->id); 
        return $vacation_goto . '@' . Config::read('vacation_domain');
    }
 
/**********************************************************************************************************************************************************
  old function from non-PFAHandler times of AliasHandler
  Will be replaced by a global delete() function in PFAHandler
 **********************************************************************************************************************************************************/

    /**
     *  @return true on success false on failure
     */
    public function delete(){
        if( ! $this->view() ) {
            $this->errormsg[] = 'An alias with that address does not exist.'; # TODO: make translatable
            return false;
        }

        if ($this->return['is_mailbox']) {
            $this->errormsg[] = 'This alias belongs to a mailbox and can\'t be deleted.'; # TODO: make translatable
            return false;
        }

        $result = db_delete('alias', 'address', $this->id);
        if( $result == 1 ) {
            list(/*NULL*/,$domain) = explode('@', $this->id);
            db_log ($domain, 'delete_alias', $this->id);
            return true;
        }
    }

 }

/* vim: set expandtab softtabstop=4 tabstop=4 shiftwidth=4: */
