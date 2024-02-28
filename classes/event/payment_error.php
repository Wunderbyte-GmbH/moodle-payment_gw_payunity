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
 * The payment_successful event.
 *
 * @package paygw_payunity
 * @copyright 2022 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payunity\event;

/**
 * The class to handle payment_successful event class.
 *
 * @package paygw_payunity
 * @property-read array $other { Extra information about event. }
 * @since Moodle 3.11
 * @copyright 2022 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_error extends \core\event\base {

    /**
     * Event init
     *
     * @return void
     *
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Get event name
     *
     * @return string
     *
     */
    public static function get_name() {
        return get_string('payment_error', 'paygw_payunity');
    }

    /**
     * Get event description
     *
     * @return string
     *
     */
    public function get_description() {
        return "The user with the id {$this->userid} has tried to pay, but an error occured: " . $this->other['message'];
    }

    /**
     * Get event url
     *
     * @return \moodle_url
     *
     */
    public function get_url() {
        return new \moodle_url('/payment/gateway/payunity/checkout.php');
    }
}
