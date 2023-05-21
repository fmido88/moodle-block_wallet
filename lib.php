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
 * wallet enrolment plugin.
 *
 * @package    block_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * To add the category and node information into the my profile page.
 *
 * @param core_user\output\myprofile\tree $tree The myprofile tree to add categories and nodes to.
 * @param stdClass                        $user The user object that the profile page belongs to.
 * @param bool                            $iscurrentuser If the $user object is the current user.
 * @param stdClass                        $course The course to determine if we are in a course context or system context.
 * @return void
 */
function block_wallet_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot.'/enrol/wallet/lib.php');
    // Get the user balance.
    $balance = enrol_wallet_plugin::get_user_balance($user->id);

    // Get the default currency.
    $currency = get_config('enrol_wallet', 'currency');

    // Prepare transaction URL to display.
    $transactionsurl = new moodle_url('/enrol/wallet/extra/transaction.php');
    $transactions = html_writer::link($transactionsurl, get_string('transactions', 'enrol_wallet'));
    $tempctx = new stdClass;
    $tempctx->balance = $balance;
    $tempctx->currency = $currency;
    $tempctx->transactions = $transactions;

    // Display the current user's balance in the wallet.
    $render = $OUTPUT->render_from_template('enrol_wallet/display', $tempctx);

    // Add the new category.
    $wdcategory = new core_user\output\myprofile\category('walletcredit',
                                                                    get_string('walletcredit', 'enrol_wallet'),
                                                                    null);
    $tree->add_category($wdcategory);

    $credittitle = ''; // No need now for a title.
    // Add the node.
    $node = new core_user\output\myprofile\node('walletcredit', 'walletcredit', $credittitle, null, null, $render);
    $tree->add_node($node);
}
