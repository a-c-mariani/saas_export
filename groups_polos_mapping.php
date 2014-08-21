<?php

defined('MOODLE_INTERNAL') || die();

print html_writer::start_tag('DIV', array('align'=>'center'));

if(isset($_POST['map_polos']) && isset($_POST['save'])) {
    $mapped_groups = $DB->get_records('saas_polos_mapping', null, 'groupname', 'groupname, id, polo_id');
    $saved = false;
    foreach($_POST['map_polos'] AS $groupname=>$poloid) {
        $decoded_groupname = urldecode($groupname);
        if(isset($mapped_groups[$decoded_groupname])) {
            if(empty($poloid)) {
                $DB->delete_records('saas_polos_mapping', array('id'=>$mapped_groups[$decoded_groupname]->id));
                $saved = true;
            } else {
                if($poloid != $mapped_groups[$decoded_groupname]->polo_id) {
                    $obj = new stdClass();
                    $obj->id = $mapped_groups[$decoded_groupname]->id;
                    $obj->polo_id = $poloid;
                    $DB->update_record('saas_polos_mapping', $obj);
                    $saved = true;
                }
            }
        } else {
            if(!empty($poloid)) {
                $obj = new stdClass();
                $obj->groupname = $decoded_groupname;
                $obj->polo_id = $poloid;
                $DB->insert_record('saas_polos_mapping', $obj);
                $saved = true;
            }
        }
    }
    $msg = $saved  ? get_string('saved', 'report_saas_export') : get_string('no_changes', 'report_saas_export');
    print $OUTPUT->heading($msg, 4, 'saas_export_message');
}

$polos = $DB->get_records_menu('saas_polos', null, 'nome', 'id, nome');

$sql = "SELECT DISTINCT g.name as groupname, polo.polo_id, polo.nome as saas_polo_nome
          FROM mdl_saas_course_mapping scm
          JOIN mdl_course c ON (c.id = scm.courseid)
          JOIN mdl_saas_ofertas_disciplinas sod ON (sod.id = scm.oferta_disciplina_id AND sod.enable = 1)
          JOIN mdl_groups g ON (g.courseid = c.id)
     LEFT JOIN (SELECT spm.groupname, spm.polo_id, sp.nome
                  FROM mdl_saas_polos_mapping spm
                  JOIN mdl_saas_polos sp ON (sp.id = spm.polo_id)) polo
            ON (polo.groupname = g.name)
      ORDER BY g.name";
$map = $DB->get_records_sql($sql);
$data = array();
foreach($map AS $groupname=>$m) {
    $poloid = empty($m->polo_id) ? 0 : $m->polo_id;
    $encoded_groupname = urlencode($groupname);
    $select = html_writer::select($polos, "map_polos[{$encoded_groupname}]", $poloid);
    $data[] = array($m->groupname, $select);
}

echo html_writer::start_tag('form', array('method'=>'post', 'action'=>'index.php'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'polo_mapping'));

$table = new html_table();
$table->head  = array(get_string('moodle_group', 'report_saas_export'), get_string('polo_saas', 'report_saas_export'));
$table->data = $data;
echo html_writer::table($table);

echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'save', 'value'=>s(get_string('save', 'admin'))));
echo html_writer::end_tag('form');

print html_writer::end_tag('DIV');
