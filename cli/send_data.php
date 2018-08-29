<?php

define('CLI_SCRIPT', true);
error_reporting(E_ALL);

if (strpos(__FILE__, '/admin/report/') !== false) {
    require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
} else {
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
}
require_once($CFG->libdir.'/clilib.php');
require_once(dirname(dirname(__FILE__)) . '/classes/saas.php');

$saas = new saas();

list($options, $unrecognized) = cli_get_params(
        array('help'     => false,
              'all'      => false,
              'ocid'     => false,
              'list'     => false,
              'nodetails'=> false,
             ),
        array('h' => 'help',
              'l' => 'list',
              'n' => 'nodetails',
              'a' => 'all',
             ));

$all = !empty($options['all']);
$list = !empty($options['list']);
$details = empty($options['nodetails']);

if (empty($options['ocid'])) {
    $ocid = 0;
} else {
    $ocid = $options['ocid'];
    if (!is_numeric($ocid)) {
        echo "\nId. da oferta de curso é inválido.\n";
        $options['help'] = true;
    } else {
        $ocs = $saas->get_ofertas($ocid);
        if (!isset($ocs[$ocid])) {
            echo "\nId. da oferta de curso não foi localizado.\n";
            $options['help'] = true;
        }
    }
}

if ($options['help'] || !empty($unrecognized) || (empty($all) && empty($ocid) && empty($list))) {
        echo "
        Exporta dados para SAAS:
          \$ php {$argv[0]} [options]

        Options:
        -h, --help                  Mostra este auxílio
        -l, --list                  Lista ofertas de curso
        -a, --all                   Exporta dados de todos as ofertas de curso
        -n, --nodetails             Não exporta detalhes dos estudantes (notas, último acesso, etc)

            --ocid=<id da oferta de curso>  Exporta dados de uma oferta de curso específica

        Exemplos:
           \$ php {$argv[0]} -a -n
           \$ php {$argv[0]} -l
           \$ php {$argv[0]} --ocid=42 -n

        \n";
        exit;
}

$CFG->debug = DEBUG_NORMAL;     // Errors, warnings and notices

$ofertas_cursos = $saas->get_ofertas_cursos();
if (empty($ofertas_cursos)) {
    echo get_string('no_ofertas_cursos', 'report_saas_export');
    return;
}

$selected_ocs = array();
if (empty($ocid) || $list) {
    $ofertas_disciplinas_oc = $saas->get_ofertas_disciplinas(0, true);

    $show_polos = $saas->get_config('polo_mapping') != 'no_polo';
    $polos_oc = $show_polos ? $saas->get_polos_by_oferta_curso() : array();

    foreach ($ofertas_cursos AS $ocid=>$oc) {
        if (isset($ofertas_disciplinas_oc[$ocid]) || ($show_polos && !empty($polos_oc[$ocid]))) {
            $selected_ocs[$ocid] = $oc;
        }
    }
} else {
    $selected_ocs[$ocid] = $ofertas_cursos[$ocid];
}

if ($list) {
    echo "\nLista de ofertas de curso passíveis de exportação:\n";
    foreach ($selected_ocs AS $id=>$oc) {
        echo "    {$id} - {$oc->nome} ({$oc->ano}/{$oc->periodo})\n";
    }
    echo "\n";
} else {
    echo 'Início: ' . date('d/m/Y H:i:s') . "\n";

    try {
        $saas->send_data($selected_ocs, $details, array(), array(), false);
        $count_sent_users = $saas->count_sent_users;
        $count_sent_users_failed = $saas->count_sent_users_failed;
        $count_sent_ods = $saas->count_sent_ods;
        $count_sent_polos = $saas->count_sent_polos;
        $elapsed_time = $saas->elapsed_time;
    } catch (Exception $e){
        var_dump($e->getMessage());
        exit;
    }

    if ($elapsed_time <= 60) {
        $msg = round($elapsed_time, 1) . ' segundos';
    } else {
        $msg = round($elapsed_time/60, 2) . ' minutos';
    }
    echo "\nFim: " . date('d/m/Y H:i:s') . "\n";
    echo 'Tempo da exportação: ' . $msg. "\n";
    ksort($saas->count_ws_calls);
    echo "\nChamadas de WS = " . var_export($saas->count_ws_calls, true) . "\n";
    ksort($saas->time_ws_calls);
    echo "\nTempos de WS = " . var_export($saas->time_ws_calls, true) . "\n";

    echo "\nOfertas de disciplinas exportadas = {$count_sent_ods}\n";
    echo "Polos exportados = {$count_sent_polos}\n";
    foreach ($count_sent_users AS $r=>$count) {
        $msg_failed = '';
        if ($count_sent_users_failed[$r] > 0) {
            $msg_failed = "\n\t- " . get_string('send_failed', 'report_saas_export', $count_sent_users_failed[$r]);
        }
        echo get_string($r.'s', 'report_saas_export') . " exportados = {$count}{$msg_failed}\n";
    }
    echo "\n";
}
