<?php
/**
 * This is a separate script that copies the GEWIS Report Database to the Web
 * database.
 *
 * It is a simple PostgreSQL to MySQL copy script.
 */

// connections
$pgconn = new PDO('pgsql:host=localhost;dbname=gewis_report;user=;password=');
$pgconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$myconn = new PDO('mysql:host=localhost;dbname=gewisweb_dev', '', '');
$myconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* which tables to sync */
$tables = [
    'Address',
    'BoardMember',
    'Decision',
    'MailingList',
    'Meeting',
    'Member',
    'members_mailinglists',
    'Organ',
    'OrganMember',
    'organs_subdecisions',
    'SubDecision'
];

// to not trip up InnoDB
$myconn->query('SET foreign_key_checks = 0');

foreach ($tables as $table) {
    $query = "SELECT * FROM $table";
    $stmt = $pgconn->query($query);
    echo "Table $table\n";

    $truncate = "TRUNCATE TABLE $table";
    $myconn->query($truncate);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fields = '(' . implode(', ', array_keys($row)) . ')';
        $values = '(' . implode(', ', array_map(function ($a) {
            return ':' . $a;
        }, array_keys($row))) . ')';

        $data = $row;

        // see if we can fetch about 256 more rows (gigantic speed increase)
        for ($i = 0; $i < 256 && ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)); $i++) {
            $values .= ', (' . implode(', ', array_map(function ($a) use ($i) {
                return ':' . $a . $i;
            }, array_keys($row2))) . ')';
            foreach ($row2 as $key => $value) {
                $data[$key . $i] = $value;
            }
        }

        $sql = "INSERT INTO $table $fields VALUES $values";
        $stmtt = $myconn->prepare($sql);

        $stmtt->execute($data);

        echo '.';
    }
    echo "\n\n";
}

$myconn->query('SET foreign_key_checks = 1');
