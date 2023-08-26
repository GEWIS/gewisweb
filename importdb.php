<?php

declare(strict_types=1);

/**
 * This is a separate script that copies the GEWIS Report Database to the website database.
 *
 * It is a simple PostgreSQL to MySQL copy script.
 */

echo 'Commencing sync with GEWISDB...' . PHP_EOL;

try {
    // Setting up connections.
    $config = include 'config/autoload/gewisdb.local.php';

    $pgconn = new PDO(
        sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;options=\'--client_encoding=%s\'',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        ),
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
    );

    $doctrineConf = include 'config/autoload/doctrine.local.php';
    $params = $doctrineConf['doctrine']['connection']['orm_default']['params'];

    $myconn = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $params['host'],
            $params['port'],
            $params['dbname'],
            $params['charset'],
        ),
        $params['user'],
        $params['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $params['charset'] . ' COLLATE ' . $params['collate'],
        ],
    );
} catch (Exception|Error $e) {
    echo 'ERROR: Failed to connect to GEWISDB or GEWISWEB.' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

/* which tables to sync */
$tables = [
    'Address',
    'BoardMember',
    'Decision',
    'MailingList',
    'Meeting',
    'Member',
    'members_mailinglists',
    'Keyholder',
    'Organ',
    'OrganMember',
    'organs_subdecisions',
    'SubDecision'
];

echo "Connection with GEWISDB and GEWISWEB set up" . PHP_EOL;
echo "Disabling foreign key constraints on GEWISWEB" . PHP_EOL;

try {
    // Disabling the foreign key constraints on GEWISWEB is necessary to silence InnoDB as (mostly) members can be
    // removed while they are still referenced elsewhere.
    $myconn->query('SET foreign_key_checks = 0');
} catch (PDOException $e) {
    echo 'ERROR: Failed to disable foreign key constraints on GEWISWEB.' . PHP_EOL;
    exit(1);
}

try {
    // Start the actual synchronisation.
    echo 'Creating restore point for GEWISWEB...' . PHP_EOL;
    $myconn->query('START TRANSACTION');
    echo 'Restore point for GEWISWEB created.' . PHP_EOL;

    $pksQuery = <<<'PKS'
SELECT TABLE_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = '%s' AND CONSTRAINT_NAME = 'PRIMARY'
PKS;
    $pks = $myconn->query(sprintf($pksQuery, $params['dbname']))
        ->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

    foreach ($tables as $table) {
        $query = sprintf('SELECT * FROM %s', $table);
        $pgStmt = $pgconn->query($query);
        echo 'Syncing table "' . $table . '"...' . PHP_EOL;

        $insertCount = 0;
        // Insert new data
        while ($pgData = $pgStmt->fetch(PDO::FETCH_ASSOC)) {
            $fields = sprintf(
                '(%s)',
                implode(
                    ', ',
                    array_keys($pgData),
                ),
            );
            $values = sprintf(
                '(%s)',
                implode(
                    ', ',
                    array_map(
                        function ($a) {
                            return ':' . $a;
                        },
                        array_keys($pgData),
                    ),
                ),
            );
            $updates = implode(
                ', ',
                array_map(
                    function ($a) {
                        return $a . '=VALUES(' . $a . ')';
                    },
                    array_keys($pgData),
                ),
            );

            $data = $pgData;

            // see if we can fetch about 256 more rows (gigantic speed increase)
            for ($i = 0; $i < 256 && ($pgData2 = $pgStmt->fetch(PDO::FETCH_ASSOC)); $i++) {
                $values .= sprintf(
                    ', (%s)',
                    implode(
                        ', ',
                        array_map(
                            function ($a) use ($i) {
                                return ':' . $a . $i;
                            },
                            array_keys($pgData2),
                        ),
                    ),
                );

                foreach ($pgData2 as $key => $value) {
                    $data[$key . $i] = $value;
                }
            }

            $sql = sprintf(
                'INSERT IGNORE INTO %s %s VALUES %s ON DUPLICATE KEY UPDATE %s',
                $table,
                $fields,
                $values,
                $updates,
            );
            $insertStmt = $myconn->prepare($sql);

            try {
                $insertStmt->execute($data);
                $insertCount += $insertStmt->rowCount();
            } catch (Exception $e) {
                echo 'ERROR: Failed to import data of table "' . $table . '"' . PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL;
            }

            echo '.';
        }

        echo PHP_EOL . PHP_EOL;
        echo 'Inserted or updated ' . $insertCount . ' rows in "' . $table . '" (updates count double)' . PHP_EOL;

        // Removing old data
        $idSql = sprintf(
            'SELECT %s FROM %s',
            $pks[$table][0],
            $table,
        );
        $ids = $pgconn->query($idSql)->fetchAll(PDO::FETCH_COLUMN);

        if (0 === count($ids)) {
            $ids = [null];
        }

        $deletionSql = sprintf(
            "DELETE FROM %s WHERE %s NOT IN (%s?)",
            $table,
            $pks[$table][0],
            str_repeat('?,', count($ids) - 1),
        );
        $removeStmt = $myconn->prepare($deletionSql);

        try {
            $removeStmt->execute($ids);
            echo 'Deleted ' . $removeStmt->rowCount() . ' rows from "' . $table . '"' . PHP_EOL;
        } catch (Exception $e) {
            echo 'ERROR: Failed to remove data from table "' . $table . '"' . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
        }

        echo PHP_EOL;
    }

    echo 'Committing transaction...' . PHP_EOL;
    $myconn->query('COMMIT');

    echo 'Transaction committed.' . PHP_EOL;
    echo 'Sync with GEWISDB completed!' . PHP_EOL;
} catch (Exception|Error $e) {
    echo 'ERROR: Sync with GEWISDB failed because of exception' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;

    echo 'Restoring GEWISWEB...' . PHP_EOL;
    try {
        $myconn->query('ROLLBACK');
        echo 'Restored GEWISWEB state.' . PHP_EOL;
    } catch (PDOException) {
        echo 'ERROR: Could not restore GEWISWEB' . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
        echo $e->getTraceAsString() . PHP_EOL;
    }
} finally {
    echo 'Enabling foreign key constraints...' . PHP_EOL;
    $myconn->query('SET foreign_key_checks = 1');
}
