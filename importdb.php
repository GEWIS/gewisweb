<?php
/**
 * This is a separate script that copies the GEWIS Report Database to the Web
 * database.
 *
 * It is a simple PostgreSQL to MySQL copy script.
 */

// connections
$pgconn = new PDO('pgsql:host=localhost;dbname=gewis_report;user=;password=');

$myconn = new PDO('mysql:host=localhost;dbname=gewisweb_dev', '', '');

/* which tables to sync */
$tables = array(
    'Address',
    'Boardmember',
    'Decision',
    'Mailinglist',
    'Meeting',
    'Member',
    'members_mailinglists',
    'Organ',
    'OrganMember',
    'SubDecision'
);

foreach ($tables as $table) {
    $query = "SELECT * FROM $table";
    $stmt = $pgconn->query($query);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fields = '(' . implode(', ', array_keys($row)) . ')';
        $values = '(' . implode(', ', array_map(function ($a) {
            return ':' . $a;
        }, array_keys($row))) . ')';

        $truncate = "TRUNCATE TABLE $table";
        $myconn->query($truncate);

        $sql = "INSERT INTO $table $fields VALUES $values";
        $stmtt = $myconn->prepare($sql);

        $stmtt->execute($row);
    }
}
