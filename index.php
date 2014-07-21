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
 *
 * @package    report
 * @subpackage saas_export
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('report/saas_export:view', context_system::instance());
admin_externalpage_setup('report_saas_export', '', null, '', array('pagelayout'=>'report'));

$baseurl = new moodle_url('/report/saas_export/index.php');
$api_key = get_config('report_saas_export', 'api_key');

$tab_items = array('guidelines'=>true, 'settings'=>true, 'saas_data'=>false, 'course_mapping'=>false, 'polo_mapping'=>false, 'overview'=>false, 'export'=>false);

$tabs = array();
foreach($tab_items AS $act=>$always) {
    if($always || !empty($api_key)) {
        $tabs[$act] = new tabobject($act, new moodle_url('/report/saas_export/index.php', array('action'=>$act)), get_string($act, 'report_saas_export'));
    }
}

$action = optional_param('action', 'guidelines' , PARAM_TEXT);
$action = isset($tabs[$action]) ? $action : 'guidelines';

$saas = new saas();

switch ($action) {
    case 'guidelines':
        echo $OUTPUT->header();
        print_tabs(array($tabs), $action);
        print get_string('saas_presentation', 'report_saas_export');
        echo $OUTPUT->footer();
        break;
    case 'settings':
        require_once($CFG->dirroot . '/report/saas_export/settings_form.php');
        $baseurl->param('action', 'settings');
        $mform = new saas_export_settings_form($baseurl);

        if ($mform->is_cancelled()) {
            redirect($baseurl);
        } else if ($data = $mform->get_data()) {
            saas::save_settings($data);
            redirect($baseurl);
        }

        echo $OUTPUT->header();
        print_tabs(array($tabs), $action);
        $mform->display();
        echo $OUTPUT->footer();
        break;
    case 'saas_data':
        echo $OUTPUT->header();
        print_tabs(array($tabs), $action);

        $saas->load_saas_data(true);
        $saas->show_table_ofertas_curso_disciplinas();
        $saas->show_table_polos();

        echo $OUTPUT->footer();
        break;
    case 'course_mapping':
        echo $OUTPUT->header();
        print_tabs(array($tabs), $action);

        switch ($saas->get_config('course_mapping')) {
            case 'one_to_one':
                include('course_mapping_one_to_one.php');
                break;
            case 'one_to_many':
            case 'many_to_one':
                print $OUTPUT->box_start('generalbox boxwidthnormal');
                print $OUTPUT->heading('Ainda estamos trabalhando. Mapeamento disponível em breve ...');
                print $OUTPUT->heading(get_string($saas->get_config('course_mapping'), 'report_saas_export'));
                print $OUTPUT->box_end();
                break;
        }

        echo $OUTPUT->footer();
        break;
    case 'polo_mapping':
        echo $OUTPUT->header();
        print_tabs(array($tabs), $action);

        include('polo_mapping.php');
        echo $OUTPUT->footer();
        break;
    default:
        echo $OUTPUT->header();
        print_tabs(array($tabs), $action);
        print $OUTPUT->box_start('generalbox boxwidthnormal');
        print $OUTPUT->heading('Ainda estamos trabalhando. Disponível em breve ...');
        print $OUTPUT->box_end();
        echo $OUTPUT->footer();
}
