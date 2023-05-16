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
 * Upgrade script for block_wallet
 *
 * File         upgrade.php
 * Encoding     UTF-8
 *
 * @package     block_wallet
 *
 * @copyright   Mohammed Farouk <phun.for.physics@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade
 *
 * @param int $oldversion old (current) plugin version
 * @return boolean
 */
function xmldb_block_wallet_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023041305) {
        // Add wallet_items table.
        $table = new xmldb_table('wallet_items');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cost', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('currency', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, 'cost');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'currency');
        // Add KEYS.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Block_wallet savepoint reached.
        upgrade_block_savepoint(true, 2023041305, 'wallet');
    }
    if ($oldversion < 2023042207) {
        // Add wallet_items table.
        $table = new xmldb_table('vc_sms');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('message', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'subject');
        $table->add_field('amount', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'message');
        $table->add_field('sender', XMLDB_TYPE_INTEGER, '13', null, false, null, null, 'amount');
        $table->add_field('time', XMLDB_TYPE_INTEGER, '20', null, true, null, null, 'sender');
        $table->add_field('done', XMLDB_TYPE_INTEGER, '1', null, true, null, null, 'time');

        // Add KEYS.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Block_wallet savepoint reached.
        upgrade_block_savepoint(true, 2023042207, 'wallet');
    }
    return true;
}
