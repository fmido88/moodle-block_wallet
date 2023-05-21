<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * block wallet plugin.
 *
 * @package    block_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use enrol_wallet\transactions;
/**
 * block wallet plugin.
 *
 * @package    block_wallet
 * @copyright  2023 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_wallet extends block_base {

    /**
     * Block Wallet init.
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_wallet');
    }

    /**
     * Getting the content of the block.
     * The display differ according to user.
     * If the user has capabilites to charge other user's wallet or
     * a regular user.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $USER, $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }
        require_once($CFG->dirroot.'/enrol/wallet/lib.php');
        $context = context_system::instance();
        // Check the capabilities of the user.
        $cancredit = has_capability('enrol/wallet:creditdebit', $context);
        $canviewcoupons = has_capability('enrol/wallet:viewcoupon', $context);
        $cangeneratecoupon = has_capability('enrol/wallet:createcoupon', $context);

        // Get the user balance.
        $balance = transactions::get_user_balance($USER->id);

        // Get the default currency.
        $currency = get_config('enrol_wallet', 'currency');
        // Get the default payment account.
        $account = get_config('enrol_wallet', 'paymentaccount');
        // Get coupons settings.
        $couponsetting = get_config('enrol_wallet', 'coupons');

        $transactionsurl = new moodle_url('/enrol/wallet/extra/transaction.php');
        $transactions = html_writer::link($transactionsurl, get_string('transactions', 'enrol_wallet'));
        $tempctx = new stdClass;
        $tempctx->balance = $balance;
        $tempctx->currency = $currency;
        $tempctx->transactions = $transactions;

        // Display the current user's balance in the wallet.
        $render = $OUTPUT->render_from_template('enrol_wallet/display', $tempctx);

        // If the user can credit others, display the charging form.
        if ($cancredit) {

            require_once($CFG->libdir.'/formslib.php');

            $mform = new \MoodleQuickForm('credit2', 'POST', $CFG->wwwroot.'/enrol/wallet/extra/charger.php');
            $mform->addElement('header', 'main', get_string('chargingoptions', 'enrol_wallet'));

            $mform->addElement('select', 'op', 'operation', ['credit' => 'credit', 'debit' => 'debit', 'balance' => 'balance']);

            $options = array(
                'ajax' => 'enrol_manual/form-potential-user-selector',
                'multiple' => false,
                'courseid' => SITEID,
                'enrolid' => 0,
                'perpage' => $CFG->maxusersperpage,
                'userfields' => implode(',', \core_user\fields::get_identity_fields($context, true))
            );
            $mform->addElement('autocomplete', 'userlist', get_string('selectusers', 'enrol_manual'), array(), $options);
            $mform->addRule('userlist', 'select user', 'required');

            $mform->addElement('text', 'value', 'Value');
            $mform->setType('value', PARAM_INT);
            $mform->hideIf('value', 'op', 'eq', 'balance');

            $mform->addElement('hidden', 'sesskey');
            $mform->setType('sesskey', PARAM_TEXT);
            $mform->setDefault('sesskey', sesskey());

            $mform->addElement('submit', 'submit', 'submit');

            ob_start();
            $mform->display();
            $output = ob_get_clean();

            $render .= $OUTPUT->box($output);
        } else {
            // Set the data we want to send to forms.
            $instance = new \stdClass;
            $data = new \stdClass;

            $instance->id = 0;
            $instance->courseid = SITEID;
            $instance->currency = $currency;
            $instance->customint1 = $account;

            $data->instance = $instance;

            // First check if payments is enabled.
            if (!empty($account)) {
                // If the user don't have capablility to charge others.
                // Display options to charge with coupons or other payment methods.
                require_once($CFG->dirroot.'/enrol/wallet/classes/form/topup_form.php');
                $topupurl = new moodle_url('/enrol/wallet/extra/topup.php');
                $topupform = new \enrol_wallet\form\topup_form($topupurl, $data);
                ob_start();
                $topupform->display();
                $render .= ob_get_clean();
            }

            // Check if fixed coupons enabled.
            if ($couponsetting == enrol_wallet_plugin::WALLET_COUPONSFIXED ||
                $couponsetting == enrol_wallet_plugin::WALLET_COUPONSALL) {
                    // Display the coupon form to enable user to topup wallet using fixed coupon.
                    require_once($CFG->dirroot.'/enrol/wallet/classes/form/applycoupon_form.php');
                    $action = new moodle_url('/enrol/wallet/extra/action.php');
                    $couponform = new \enrol_wallet\form\applycoupon_form($action, $data);
                    ob_start();
                    $couponform->display();
                    $render .= ob_get_clean();
            }
        }

        // Check if the user can view and generate coupons.
        if ($canviewcoupons) {
            $url = new moodle_url('/enrol/wallet/extra/coupontable.php');
            $render .= html_writer::link($url, get_string('coupon_table', 'enrol_wallet')).'<br>';

            if ($cangeneratecoupon) {
                $url = new moodle_url('/enrol/wallet/extra/coupon.php');
                $render .= html_writer::link($url, get_string('coupon_generation', 'enrol_wallet'));
            }
        }

        $this->content = new stdClass();
        $this->content->text = $render;

        return $this->content;
    }

    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     *
     * @return void
     */
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('pluginname', 'block_wallet');
            } else {
                $this->title = $this->config->title;
            }
        }
    }

    /**
     * Are you going to allow multiple instances of each block?
     * If yes, then it is assumed that the block WILL USE per-instance configuration
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * We don't have a config file as we rely on enrol_wallet.
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Default return is false - header will be shown
     * @return bool
     */
    public function hide_header() {
        return false;
    }

}
