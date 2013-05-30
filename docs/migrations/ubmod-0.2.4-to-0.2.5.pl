#!/usr/bin/env perl
use strict;
use warnings;
use Config::Tiny;
use DBI;

# Set this variable to the path of your settings.ini file.
my $config_file = '/etc/ubmod/settings.ini';

confirm_or_exit(
    "Is your settings.ini file located at '$config_file'? (y/n): ");

die "File '$config_file' not found\n"    unless -f $config_file;
die "File '$config_file' not readable\n" unless -r $config_file;

my $config = Config::Tiny->read($config_file);

die "Failed to read config file '$config_file': "
    . Config::Tiny->errstr() . "\n"
    unless defined $config;

my $dbh = db_connect( $config->{database} );

print <<"EOF";
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
This script will update your database from UBMoD 0.2.4 to UBMoD 0.2.5.
If your database has any modifications or is not using the schema from
UBMoD 0.2.4, this process will fail.
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
Back up all UBMoD data before continuing.
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
EOF

confirm_or_exit("Are you sure you want to continue (y/n): ");

my $pbs_count = get_count( $dbh, 'pbs_event' );
my $sge_count = get_count( $dbh, 'sge_event' );

my $format;

if ( $pbs_count == 0 && $sge_count == 0 ) {
    print "No data found, you do not need to use this script.\n";
    exit;
}
elsif ( $pbs_count > 0 && $sge_count > 0 ) {
    print "Data found for both SGE and PBS.\n";
    exit 1;
}
elsif ( $pbs_count > 0 ) {
    $format = 'pbs';
}
elsif ( $sge_count > 0 ) {
    $format = 'sge';
}

print "Starting database migration.\n\n";

my @stmts = (
    q{ALTER TABLE `event` ADD COLUMN `source_format` ENUM('pbs','sge','slurm') NOT NULL AFTER `event_id`},
    qq{UPDATE `event` SET `source_format` = '$format'},
    q{ALTER TABLE `event` MODIFY COLUMN `date_key` date NOT NULL},
    q{ALTER TABLE `event` MODIFY COLUMN `account` varchar(255) NOT NULL DEFAULT 'Unknown'},
    q{ALTER TABLE `event` MODIFY COLUMN `project` varchar(255) NOT NULL DEFAULT 'Unknown'},
    q{ALTER TABLE `event` MODIFY COLUMN `cput` bigint unsigned DEFAULT NULL},
    q{ALTER TABLE `event` MODIFY COLUMN `mem` bigint unsigned DEFAULT NULL},
    q{ALTER TABLE `event` MODIFY COLUMN `vmem` bigint unsigned DEFAULT NULL},
    q{ALTER TABLE `event` ADD KEY `source` (`source_format`,`cluster`)},

    q{ALTER TABLE `pbs_event` DROP COLUMN `date_key`},
    q{ALTER TABLE `pbs_event` MODIFY COLUMN `job_array_index` int DEFAULT '-1'},
    q{UPDATE `pbs_event` SET `job_array_index` = '-1' WHERE `job_array_index` IS NULL},
    q{ALTER TABLE `pbs_event` MODIFY COLUMN `job_array_index` int NOT NULL DEFAULT '-1'},
    q{DELETE FROM `pbs_event` WHERE `type` != 'E'},
    q{ALTER TABLE `pbs_event` DROP KEY `type`},
    q{ALTER TABLE `pbs_event` DROP COLUMN `type`},
    q{ALTER TABLE `pbs_event` ADD UNIQUE KEY `job` (`host`,`job_id`,`job_array_index`,`ctime`)},

    q{ALTER TABLE `sge_event` DROP KEY `hostname`},
    q{ALTER TABLE `sge_event` ADD UNIQUE KEY `job` (`hostname`,`job_number`,`task_number`,`failed`)},

    q{
        CREATE TABLE `slurm_event` (
          `slurm_event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `jobid` int unsigned NOT NULL,
          `jobname` tinytext NOT NULL,
          `cluster` tinytext NOT NULL,
          `partition` tinytext NOT NULL,
          `user` tinytext NOT NULL,
          `group` tinytext NOT NULL,
          `account` tinytext NOT NULL,
          `submit` datetime NOT NULL,
          `eligible` datetime NOT NULL,
          `start` datetime NOT NULL,
          `end` datetime NOT NULL,
          `exitcode` tinytext NOT NULL,
          `nnodes` int unsigned NOT NULL,
          `ncpus` int unsigned NOT NULL,
          `nodelist` text NOT NULL,
          PRIMARY KEY (`slurm_event_id`),
          UNIQUE KEY `job` (`cluster`(20),`jobid`,`submit`)
        ) ENGINE=MyISAM
    },
    q{DROP PROCEDURE IF EXISTS UpdateJobFacts},
    q{
        CREATE PROCEDURE UpdateJobFacts()
        BEGIN
          TRUNCATE `fact_job`;

          INSERT INTO `fact_job` (
            `dim_date_id`,
            `dim_cluster_id`,
            `dim_queue_id`,
            `dim_user_id`,
            `dim_group_id`,
            `dim_tags_id`,
            `dim_cpus_id`,
            `wallt`,
            `cput`,
            `mem`,
            `vmem`,
            `wait`,
            `exect`,
            `nodes`,
            `cpus`
          )
          SELECT
            `dim_date`.`dim_date_id`,
            `dim_cluster`.`dim_cluster_id`,
            `dim_queue`.`dim_queue_id`,
            `dim_user`.`dim_user_id`,
            `dim_group`.`dim_group_id`,
            `dim_tags`.`dim_tags_id`,
            `dim_cpus`.`dim_cpus_id`,
            `event`.`wallt`,
            `event`.`cput`,
            `event`.`mem`,
            `event`.`vmem`,
            `event`.`wait`,
            `event`.`exect`,
            `event`.`nodes`,
            `event`.`cpus`
          FROM `event`
          JOIN `dim_date`    ON `event`.`date_key` = `dim_date`.`date`
          JOIN `dim_cluster` ON `event`.`cluster`  = `dim_cluster`.`name`
          JOIN `dim_queue`   ON `event`.`queue`    = `dim_queue`.`name`
          JOIN `dim_user`    ON `event`.`user`     = `dim_user`.`name`
          JOIN `dim_group`   ON `event`.`group`    = `dim_group`.`name`
          JOIN `dim_tags`    ON `event`.`tags`     = `dim_tags`.`tags`
          JOIN `dim_cpus`    ON `event`.`cpus`     = `dim_cpus`.`cpu_count`;
        END
    }
);

for my $sql (@stmts) {
    print "$sql\n";
    $dbh->do($sql);
}

print "\nDatabase migration complete.\n\n";

exit;

sub db_connect {
    my ($args) = @_;

    for my $arg (qw( host dbname user password )) {
        die "Missing database config option: '$arg'"
            unless defined $args->{$arg};
    }

    my $dsn = "DBI:mysql:database=$args->{dbname};host=$args->{host}";

    $dsn .= ";port=$args->{port}" if defined $args->{port};

    my $dbh = eval {
        DBI->connect( $dsn, $args->{user}, $args->{password},
            { PrintError => 0, RaiseError => 1, AutoCommit => 1 } );
    };
    if ($@) {
        die "Failed to connect to database: $@";
    }

    return $dbh;
}

sub get_count {
    my ( $dbh, $table ) = @_;

    my $sql = qq{SELECT COUNT(*) FROM `$table`};

    my ($count) = $dbh->selectrow_array($sql);

    return $count;
}

sub confirm_or_exit {
    my ($msg) = @_;

    print $msg;

    my $input = lc readline(STDIN);

    chomp($input);

    if ( $input eq 'n' ) {
        print "Exiting\n";
        exit;
    }
    elsif ( $input ne 'y' ) {
        print "Unrecognized response '$input'\n";
        exit 1;
    }
}

