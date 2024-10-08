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
     * If the user has capabilities to charge other user's wallet or
     * a regular user.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $USER, $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        require_once($CFG->dirroot.'/enrol/wallet/lib.php');
        require_once($CFG->dirroot.'/enrol/wallet/locallib.php');

        // Check the capabilities of the user.
        $cancredit = has_capability('enrol/wallet:creditdebit', context_system::instance());

        // Display the current user's balance in the wallet.
        $render = enrol_wallet_display_current_user_balance();

        // If the user can credit others, display the charging form.
        if ($cancredit) {

            $form = enrol_wallet_display_charger_form();

            $render .= $OUTPUT->box($form);
            $render .= enrol_wallet_display_coupon_urls();
        } else {
            $render .= enrol_wallet_display_topup_options();
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
        return true;
    }

    /**
     * Default return is false - header will be shown
     * @return bool
     */
    public function hide_header() {
        return false;
    }

}
