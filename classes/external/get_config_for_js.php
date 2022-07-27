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
 * This class contains a list of webservice functions related to the PayUnity payment gateway.
 *
 * @package    paygw_payunity
 * @copyright  2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_payunity\external;

use core_payment\helper;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use paygw_payunity\task\check_status;
use stdClass;
use DateTime;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_config_for_js extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
        ]);
    }

    public static function requestid(string $amount, string $currency, string $paymenttype, string $secret, string $entityid
    , $environment, $merchanttransactionid) {
        if ($environment === 'sandbox') {
            $verify = false;
            $url = "https://eu-test.oppwa.com/v1/checkouts";
        } else {
            $verify = true;
            $url = "https://eu-prod.oppwa.com/v1/checkouts";
        }
        $data = "entityId=" . $entityid . "&amount=" . $amount .
                    "&currency=" . $currency .
                    "&paymentType=" . $paymenttype . "&merchantTransactionId=" . $merchanttransactionid;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                       'Authorization:Bearer ' . $secret));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responsedata = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $responsedata;
    }

    /**
     * Returns the config values required by the PayUnity JavaScript SDK.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        GLOBAL $CFG, $USER, $SESSION;
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);

        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payunity');
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('payunity');

        $language = $SESSION->lang;
        $amount = number_format($payable->get_amount(), 2);
        $currency = $payable->get_currency();
        $secret = $config['secret'];
        $entityid = $config['clientid'];
        $root = $CFG->wwwroot;
        $environment = $config['environment'];

        $string = bin2hex(openssl_random_pseudo_bytes(8));
        $now = new DateTime();
        $timestamp = $now->getTimestamp();
        $merchanttransactionid = $string . $timestamp;

        $responsedata = self::requestid($amount, $currency, 'DB', $secret, $entityid, $environment, $merchanttransactionid );
        $data = json_decode($responsedata);

        if ($data->id !== null) {
            $purchaseid = $data->id;

            // Create Task to check status after 30 minutes.
            $userid = $USER->id;
            $now = time();
            $nextruntime = strtotime('+30 min', $now);

            $taskdata = new stdClass();
            $taskdata->orderid = $purchaseid;
            $taskdata->amount = $amount;
            $taskdata->currency = $currency;
            $taskdata->resourcepath = "/v1/checkouts/$purchaseid/payment";
            $taskdata->component = $component;
            $taskdata->paymentarea = $paymentarea;
            $taskdata->itemid = $itemid;

            $checkstatustask = new check_status();
            $checkstatustask->set_userid($userid);
            $checkstatustask->set_next_run_time($nextruntime);
            $checkstatustask->set_custom_data($taskdata);
            \core\task\manager::reschedule_or_queue_adhoc_task($checkstatustask);

        }

        return [
            'clientid' => $config['clientid'],
            'brandname' => $config['brandname'],
            'cost' => helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge),
            'currency' => $payable->get_currency(),
            'purchaseid' => $purchaseid,
            'rooturl' => $root,
            'environment' => $environment,
            'language' => $language,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'clientid' => new external_value(PARAM_TEXT, 'PayUnity client ID'),
            'brandname' => new external_value(PARAM_TEXT, 'Brand name'),
            'cost' => new external_value(PARAM_FLOAT, 'Cost with gateway surcharge'),
            'currency' => new external_value(PARAM_TEXT, 'Currency'),
            'purchaseid' => new external_value(PARAM_TEXT, 'Purchase Id'),
            'rooturl' => new external_value(PARAM_TEXT, 'Moodle Root URI'),
            'environment' => new external_value(PARAM_TEXT, 'Prod or Sandbox'),
            'language' => new external_value(PARAM_TEXT, 'Prod or Sandbox'),
        ]);
    }
}
