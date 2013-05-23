/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 */

/**
 * Original Author: Andrew E. Bruno (CCR);
 * Author: Jeffrey T. Palmer (CCR);
 * Contributor(s): -;
 */

--
-- Resource manager events
--

DROP TABLE IF EXISTS `pbs_event`;
CREATE TABLE `pbs_event` (
  `pbs_event_id`              bigint unsigned AUTO_INCREMENT NOT NULL,
  `date_key`                  datetime,
  `job_id`                    int unsigned NOT NULL,
  `job_array_index`           int unsigned,
  `host`                      varchar(255) NOT NULL,
  `queue`                     varchar(255),
  `user`                      varchar(255),
  `group`                     varchar(255),
  `ctime`                     int,
  `qtime`                     int,
  `start`                     int,
  `end`                       int,
  `etime`                     int,
  `exit_status`               int,
  `session`                   int unsigned,
  `requestor`                 varchar(255),
  `jobname`                   varchar(255),
  `owner`                     varchar(255),
  `account`                   varchar(255),
  `session_id`                int,
  `error_path`                varchar(255),
  `output_path`               varchar(255),
  `exec_host`                 text,
  `resources_used_vmem`       bigint unsigned,
  `resources_used_mem`        bigint unsigned,
  `resources_used_walltime`   bigint unsigned,
  `resources_used_nodes`      int unsigned,
  `resources_used_cpus`       int unsigned,
  `resources_used_cput`       bigint unsigned,
  `resource_list_nodes`       text,
  `resource_list_procs`       text,
  `resource_list_neednodes`   text,
  `resource_list_pcput`       bigint unsigned,
  `resource_list_cput`        bigint unsigned,
  `resource_list_walltime`    bigint unsigned,
  `resource_list_ncpus`       tinyint unsigned,
  `resource_list_nodect`      int unsigned,
  `resource_list_mem`         bigint unsigned,
  `resource_list_pmem`        bigint unsigned,
  PRIMARY KEY (`pbs_event_id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `sge_event`;
CREATE TABLE `sge_event` (
  `sge_event_id`                   bigint unsigned AUTO_INCREMENT NOT NULL,
  `cluster`                        varchar(255),
  `qname`                          varchar(255),
  `hostname`                       varchar(255) NOT NULL,
  `group`                          varchar(255),
  `owner`                          varchar(255),
  `job_name`                       varchar(255),
  `job_number`                     int unsigned NOT NULL,
  `account`                        varchar(255),
  `priority`                       tinyint,
  `submission_time`                int,
  `start_time`                     int,
  `end_time`                       int,
  `failed`                         int,
  `exit_status`                    int,
  `ru_wallclock`                   int,
  `ru_utime`                       DECIMAL(32,6),
  `ru_stime`                       DECIMAL(32,6),
  `ru_maxrss`                      int,
  `ru_ixrss`                       int,
  `ru_ismrss`                      int,
  `ru_idrss`                       int,
  `ru_isrss`                       int,
  `ru_minflt`                      int,
  `ru_majflt`                      int,
  `ru_nswap`                       int,
  `ru_inblock`                     int,
  `ru_oublock`                     int,
  `ru_msgsnd`                      int,
  `ru_msgrcv`                      int,
  `ru_nsignals`                    int,
  `ru_nvcsw`                       int,
  `ru_nivcsw`                      int,
  `project`                        varchar(255),
  `department`                     varchar(255),
  `granted_pe`                     varchar(255),
  `slots`                          int,
  `task_number`                    int,
  `cpu`                            DECIMAL(32,6),
  `mem`                            DECIMAL(32,6),
  `io`                             DECIMAL(32,6),
  `category`                       text,
  `iow`                            DECIMAL(32,6),
  `pe_taskid`                      int,
  `maxvmem`                        bigint,
  `arid`                           int,
  `ar_submission_time`             int,
  `resource_list_arch`             varchar(255),
  `resource_list_qname`            varchar(255),
  `resource_list_hostname`         varchar(255),
  `resource_list_notify`           int,
  `resource_list_calendar`         varchar(255),
  `resource_list_min_cpu_interval` int,
  `resource_list_tmpdir`           varchar(255),
  `resource_list_seq_no`           int,
  `resource_list_s_rt`             bigint,
  `resource_list_h_rt`             bigint,
  `resource_list_s_cpu`            bigint,
  `resource_list_h_cpu`            bigint,
  `resource_list_s_data`           bigint,
  `resource_list_h_data`           bigint,
  `resource_list_s_stack`          bigint,
  `resource_list_h_stack`          bigint,
  `resource_list_s_core`           bigint,
  `resource_list_h_core`           bigint,
  `resource_list_s_rss`            bigint,
  `resource_list_h_rss`            bigint,
  `resource_list_slots`            varchar(255),
  `resource_list_s_vmem`           bigint,
  `resource_list_h_vmem`           bigint,
  `resource_list_s_fsize`          bigint,
  `resource_list_h_fsize`          bigint,
  `resource_list_num_proc`         int,
  `resource_list_mem_free`         bigint,
  PRIMARY KEY (`sge_event_id`),
  UNIQUE KEY (`hostname`,`job_number`,`task_number`,`failed`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `slurm_event`;
CREATE TABLE `slurm_event` (
  `slurm_event_id` bigint unsigned AUTO_INCREMENT NOT NULL,
  `jobid`          int unsigned NOT NULL,
  `jobname`        tinytext NOT NULL,
  `cluster`        tinytext NOT NULL,
  `partition`      tinytext NOT NULL,
  `user`           tinytext NOT NULL,
  `group`          tinytext NOT NULL,
  `account`        tinytext NOT NULL,
  `submit`         datetime NOT NULL,
  `eligible`       datetime NOT NULL,
  `start`          datetime NOT NULL,
  `end`            datetime NOT NULL,
  `exitcode`       tinytext NOT NULL,
  `nnodes`         int unsigned NOT NULL,
  `ncpus`          int unsigned NOT NULL,
  `nodelist`       text NOT NULL,
  PRIMARY KEY (`slurm_event_id`)
) ENGINE=MyISAM;

--
-- Generic events
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `event_id`                  bigint unsigned AUTO_INCREMENT NOT NULL,
  `source_format`             varchar(255),
  `date_key`                  datetime NOT NULL,
  `job_id`                    int unsigned NOT NULL,
  `job_array_index`           int unsigned,
  `job_name`                  varchar(255),
  `cluster`                   varchar(255) NOT NULL,
  `queue`                     varchar(255) NOT NULL,
  `user`                      varchar(255) NOT NULL,
  `group`                     varchar(255) NOT NULL,
  `tags`                      varchar(255) NOT NULL default '[]',
  `account`                   varchar(255) NOT NULL default 'Unknown',
  `project`                   varchar(255) NOT NULL default 'Unknown',
  `start_time`                datetime NOT NULL,
  `end_time`                  datetime NOT NULL,
  `submission_time`           datetime NOT NULL,
  `wallt`                     bigint unsigned NOT NULL,
  `cput`                      bigint unsigned,
  `mem`                       bigint unsigned,
  `vmem`                      bigint unsigned,
  `wait`                      bigint unsigned NOT NULL,
  `exect`                     bigint unsigned NOT NULL,
  `nodes`                     int unsigned NOT NULL,
  `cpus`                      int unsigned NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=MyISAM;

--
-- Time intervals
--

DROP TABLE IF EXISTS `time_interval`;
CREATE TABLE `time_interval` (
  `time_interval_id` int unsigned NOT NULL AUTO_INCREMENT,
  `display_name`     varchar(255) NOT NULL,
  `start`            date,
  `end`              date,
  `custom`           tinyint,
  `query_params`     varchar(255),
  PRIMARY KEY (`time_interval_id`)
) ENGINE=MyISAM;

--
-- Dimensions
--

DROP TABLE IF EXISTS `dim_date`;
CREATE TABLE `dim_date` (
  `dim_date_id`   int unsigned NOT NULL AUTO_INCREMENT,
  `date`          date,
  `week`          tinyint unsigned,
  `month`         tinyint unsigned,
  `quarter`       tinyint unsigned,
  `year`          int     unsigned,
  `last_7_days`   tinyint unsigned,
  `last_30_days`  tinyint unsigned,
  `last_90_days`  tinyint unsigned,
  `last_365_days` tinyint unsigned,
  PRIMARY KEY (`dim_date_id`),
  KEY (`date`),
  KEY (`week`),
  KEY (`month`),
  KEY (`quarter`),
  KEY (`year`),
  KEY (`last_7_days`),
  KEY (`last_30_days`),
  KEY (`last_90_days`),
  KEY (`last_365_days`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_cluster`;
CREATE TABLE `dim_cluster` (
  `dim_cluster_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name`           varchar(255) NOT NULL,
  `display_name`   varchar(255),
  PRIMARY KEY (`dim_cluster_id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_queue`;
CREATE TABLE `dim_queue` (
  `dim_queue_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name`         varchar(255) NOT NULL,
  `display_name` varchar(255),
  PRIMARY KEY (`dim_queue_id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_user`;
CREATE TABLE `dim_user` (
  `dim_user_id`   int unsigned NOT NULL AUTO_INCREMENT,
  `name`          varchar(255) NOT NULL,
  `display_name`  varchar(255),
  `current_group` varchar(255),
  PRIMARY KEY (`dim_user_id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_group`;
CREATE TABLE `dim_group` (
  `dim_group_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name`         varchar(255) NOT NULL,
  `display_name` varchar(255),
  PRIMARY KEY (`dim_group_id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_tags`;
CREATE TABLE `dim_tags` (
  `dim_tags_id` int unsigned NOT NULL AUTO_INCREMENT,
  `tags`        varchar(255) NOT NULL,
  PRIMARY KEY (`dim_tags_id`),
  UNIQUE KEY (`tags`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_tag`;
CREATE TABLE `dim_tag` (
  `dim_tag_id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id`  int unsigned,
  `path`       varchar(255) default '/',
  `name`       varchar(255) NOT NULL,
  `key`        varchar(255),
  `value`      varchar(255),
  PRIMARY KEY (`dim_tag_id`),
  KEY `parent` (`parent_id`),
  UNIQUE KEY (`name`),
  KEY (`key`,`value`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dim_cpus`;
CREATE TABLE `dim_cpus` (
  `dim_cpus_id`  int unsigned NOT NULL AUTO_INCREMENT,
  `cpu_count`    int unsigned NOT NULL,
  `display_name` varchar(255),
  `view_order`   int unsigned,
  PRIMARY KEY (`dim_cpus_id`),
  UNIQUE KEY (`cpu_count`),
  KEY (`display_name`),
  KEY (`view_order`)
) ENGINE=MyISAM;

--
-- Bridges
--

DROP TABLE IF EXISTS `br_user_to_tag`;
CREATE TABLE `br_user_to_tag` (
  `dim_user_id` int unsigned NOT NULL,
  `dim_tag_id`  int unsigned NOT NULL,
  PRIMARY KEY (`dim_user_id`,`dim_tag_id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `br_tags_to_tag`;
CREATE TABLE `br_tags_to_tag` (
  `dim_tags_id` int unsigned NOT NULL,
  `dim_tag_id`  int unsigned NOT NULL,
  PRIMARY KEY (`dim_tags_id`,`dim_tag_id`)
) ENGINE=MyISAM;

--
-- Roll-Up Dimensions
--

DROP TABLE IF EXISTS `dim_timespan`;
CREATE TABLE `dim_timespan` (
  `dim_timespan_id` int     unsigned NOT NULL AUTO_INCREMENT,
  `month`           tinyint unsigned,
  `quarter`         tinyint unsigned,
  `year`            int     unsigned,
  `last_7_days`     tinyint unsigned NOT NULL,
  `last_30_days`    tinyint unsigned NOT NULL,
  `last_90_days`    tinyint unsigned NOT NULL,
  `last_365_days`   tinyint unsigned NOT NULL,
  PRIMARY KEY (`dim_timespan_id`),
  KEY (`month`),
  KEY (`quarter`),
  KEY (`year`),
  KEY (`last_7_days`),
  KEY (`last_30_days`),
  KEY (`last_90_days`),
  KEY (`last_365_days`)
) ENGINE=MyISAM;

--
-- Facts
--

DROP TABLE IF EXISTS `fact_job`;
CREATE TABLE `fact_job` (
  `fact_job_id`    int    unsigned NOT NULL AUTO_INCREMENT,
  `dim_date_id`    int    unsigned NOT NULL,
  `dim_cluster_id` int    unsigned NOT NULL,
  `dim_queue_id`   int    unsigned NOT NULL,
  `dim_user_id`    int    unsigned NOT NULL,
  `dim_group_id`   int    unsigned NOT NULL,
  `dim_tags_id`    int    unsigned NOT NULL,
  `dim_cpus_id`    int    unsigned NOT NULL,
  `wallt`          bigint unsigned NOT NULL,
  `cput`           bigint unsigned NOT NULL,
  `mem`            bigint unsigned NOT NULL,
  `vmem`           bigint unsigned NOT NULL,
  `wait`           bigint unsigned NOT NULL,
  `exect`          bigint unsigned NOT NULL,
  `nodes`          int    unsigned NOT NULL,
  `cpus`           int    unsigned NOT NULL,
  PRIMARY KEY (`fact_job_id`),
  KEY (`dim_date_id`,`dim_cluster_id`,`dim_queue_id`,`dim_user_id`,`dim_group_id`,`dim_tags_id`,`dim_cpus_id`),
  KEY (`dim_user_id`,`dim_date_id`,`dim_group_id`)
) ENGINE=MyISAM;

--
-- Aggregates
--

DROP TABLE IF EXISTS `agg_job_by_all`;
CREATE TABLE `agg_job_by_all` (
  `agg_job_by_all_id`  int    unsigned NOT NULL AUTO_INCREMENT,
  `dim_date_id`        int    unsigned NOT NULL,
  `dim_cluster_id`     int    unsigned NOT NULL,
  `dim_queue_id`       int    unsigned NOT NULL,
  `dim_user_id`        int    unsigned NOT NULL,
  `dim_group_id`       int    unsigned NOT NULL,
  `dim_tags_id`        int    unsigned NOT NULL,
  `dim_cpus_id`        int    unsigned NOT NULL,
  `fact_job_count`     int    unsigned NOT NULL,
  `wallt_sum`          bigint unsigned NOT NULL,
  `wallt_max`          bigint unsigned NOT NULL,
  `cput_sum`           bigint unsigned NOT NULL,
  `cput_max`           bigint unsigned NOT NULL,
  `mem_sum`            bigint unsigned NOT NULL,
  `mem_max`            bigint unsigned NOT NULL,
  `vmem_sum`           bigint unsigned NOT NULL,
  `vmem_max`           bigint unsigned NOT NULL,
  `wait_sum`           bigint unsigned NOT NULL,
  `exect_sum`          bigint unsigned NOT NULL,
  `nodes_sum`          bigint unsigned NOT NULL,
  `nodes_max`          int    unsigned NOT NULL,
  `cpus_sum`           bigint unsigned NOT NULL,
  `cpus_max`           int    unsigned NOT NULL,
  PRIMARY KEY (`agg_job_by_all_id`),
  KEY (`dim_date_id`,`dim_cluster_id`,`dim_queue_id`,`dim_user_id`,`dim_group_id`,`dim_tags_id`,`dim_cpus_id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `agg_job_by_timespan`;
CREATE TABLE `agg_job_by_timespan` (
  `agg_job_by_timespan_id` int    unsigned NOT NULL AUTO_INCREMENT,
  `dim_timespan_id`        int    unsigned NOT NULL,
  `dim_cluster_id`         int    unsigned NOT NULL,
  `dim_queue_id`           int    unsigned NOT NULL,
  `dim_user_id`            int    unsigned NOT NULL,
  `dim_group_id`           int    unsigned NOT NULL,
  `dim_tags_id`            int    unsigned NOT NULL,
  `dim_cpus_id`            int    unsigned NOT NULL,
  `fact_job_count`         int    unsigned NOT NULL,
  `wallt_sum`              bigint unsigned NOT NULL,
  `wallt_max`              bigint unsigned NOT NULL,
  `cput_sum`               bigint unsigned NOT NULL,
  `cput_max`               bigint unsigned NOT NULL,
  `mem_sum`                bigint unsigned NOT NULL,
  `mem_max`                bigint unsigned NOT NULL,
  `vmem_sum`               bigint unsigned NOT NULL,
  `vmem_max`               bigint unsigned NOT NULL,
  `wait_sum`               bigint unsigned NOT NULL,
  `exect_sum`              bigint unsigned NOT NULL,
  `nodes_sum`              bigint unsigned NOT NULL,
  `nodes_max`              int    unsigned NOT NULL,
  `cpus_sum`               bigint unsigned NOT NULL,
  `cpus_max`               int    unsigned NOT NULL,
  PRIMARY KEY (`agg_job_by_timespan_id`),
  KEY (`dim_timespan_id`,`dim_cluster_id`,`dim_queue_id`,`dim_user_id`,`dim_group_id`,`dim_tags_id`,`dim_cpus_id`)
) ENGINE=MyISAM;

--
-- Stored procedures
--

DELIMITER //

--
-- Roll-up dimensions
--

DROP PROCEDURE IF EXISTS UpdateRollUpDimensions//
CREATE PROCEDURE UpdateRollUpDimensions()
BEGIN
  CALL UpdateTimespanDimension();
END//

DROP PROCEDURE IF EXISTS UpdateTimespanDimension//
CREATE PROCEDURE UpdateTimespanDimension()
BEGIN
  TRUNCATE `dim_timespan`;

  INSERT INTO `dim_timespan` (
    `month`,
    `quarter`,
    `year`,
    `last_7_days`,
    `last_30_days`,
    `last_90_days`,
    `last_365_days`
  )
  SELECT DISTINCT
    `month`,
    `quarter`,
    `year`,
    `last_7_days`,
    `last_30_days`,
    `last_90_days`,
    `last_365_days`
  FROM `dim_date`;
END//

--
-- Facts
--

DROP PROCEDURE IF EXISTS UpdateJobFacts//
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
  JOIN `dim_date`    ON DATE(`event`.`date_key`) = `dim_date`.`date`
  JOIN `dim_cluster` ON `event`.`cluster`        = `dim_cluster`.`name`
  JOIN `dim_queue`   ON `event`.`queue`          = `dim_queue`.`name`
  JOIN `dim_user`    ON `event`.`user`           = `dim_user`.`name`
  JOIN `dim_group`   ON `event`.`group`          = `dim_group`.`name`
  JOIN `dim_tags`    ON `event`.`tags`           = `dim_tags`.`tags`
  JOIN `dim_cpus`    ON `event`.`cpus`           = `dim_cpus`.`cpu_count`;
END//

--
-- Aggregates
--

DROP PROCEDURE IF EXISTS UpdateJobAggregates//
CREATE PROCEDURE UpdateJobAggregates()
BEGIN
  CALL UpdateJobAggregateByAll();
  CALL UpdateJobAggregateByTimespan();
END//

DROP PROCEDURE IF EXISTS UpdateJobAggregateByAll//
CREATE PROCEDURE UpdateJobAggregateByAll()
BEGIN
  TRUNCATE `agg_job_by_all`;

  INSERT INTO `agg_job_by_all` (
    `dim_date_id`,
    `dim_cluster_id`,
    `dim_queue_id`,
    `dim_user_id`,
    `dim_group_id`,
    `dim_tags_id`,
    `dim_cpus_id`,
    `fact_job_count`,
    `wallt_sum`,
    `wallt_max`,
    `cput_sum`,
    `cput_max`,
    `mem_sum`,
    `mem_max`,
    `vmem_sum`,
    `vmem_max`,
    `wait_sum`,
    `exect_sum`,
    `nodes_sum`,
    `nodes_max`,
    `cpus_sum`,
    `cpus_max`
  )
  SELECT
    `fact_job`.`dim_date_id`,
    `fact_job`.`dim_cluster_id`,
    `fact_job`.`dim_queue_id`,
    `fact_job`.`dim_user_id`,
    `fact_job`.`dim_group_id`,
    `fact_job`.`dim_tags_id`,
    `fact_job`.`dim_cpus_id`,
    COUNT(*),
    SUM(`wallt`),
    MAX(`wallt`),
    SUM(`cput`),
    MAX(`cput`),
    SUM(`mem`),
    MAX(`mem`),
    SUM(`vmem`),
    MAX(`vmem`),
    SUM(`wait`),
    SUM(`exect`),
    SUM(`nodes`),
    MAX(`nodes`),
    SUM(`cpus`),
    MAX(`cpus`)
  FROM `fact_job`
  GROUP BY
    `fact_job`.`dim_date_id`,
    `fact_job`.`dim_cluster_id`,
    `fact_job`.`dim_queue_id`,
    `fact_job`.`dim_user_id`,
    `fact_job`.`dim_group_id`,
    `fact_job`.`dim_tags_id`,
    `fact_job`.`dim_cpus_id`;
END//

DROP PROCEDURE IF EXISTS UpdateJobAggregateByTimespan//
CREATE PROCEDURE UpdateJobAggregateByTimespan()
BEGIN
  TRUNCATE `agg_job_by_timespan`;

  INSERT INTO `agg_job_by_timespan` (
    `dim_timespan_id`,
    `dim_cluster_id`,
    `dim_queue_id`,
    `dim_user_id`,
    `dim_group_id`,
    `dim_tags_id`,
    `dim_cpus_id`,
    `fact_job_count`,
    `wallt_sum`,
    `wallt_max`,
    `cput_sum`,
    `cput_max`,
    `mem_sum`,
    `mem_max`,
    `vmem_sum`,
    `vmem_max`,
    `wait_sum`,
    `exect_sum`,
    `nodes_sum`,
    `nodes_max`,
    `cpus_sum`,
    `cpus_max`
  )
  SELECT
    `dim_timespan`.`dim_timespan_id`,
    `fact_job`.`dim_cluster_id`,
    `fact_job`.`dim_queue_id`,
    `fact_job`.`dim_user_id`,
    `fact_job`.`dim_group_id`,
    `fact_job`.`dim_tags_id`,
    `fact_job`.`dim_cpus_id`,
    COUNT(*),
    SUM(`wallt`),
    MAX(`wallt`),
    SUM(`cput`),
    MAX(`cput`),
    SUM(`mem`),
    MAX(`mem`),
    SUM(`vmem`),
    MAX(`vmem`),
    SUM(`wait`),
    SUM(`exect`),
    SUM(`nodes`),
    MAX(`nodes`),
    SUM(`cpus`),
    MAX(`cpus`)
  FROM `fact_job`
  JOIN `dim_date` ON `fact_job`.`dim_date_id` = `dim_date`.`dim_date_id`
  JOIN `dim_timespan` ON
        `dim_date`.`month`         = `dim_timespan`.`month`
    AND `dim_date`.`year`          = `dim_timespan`.`year`
    AND `dim_date`.`last_7_days`   = `dim_timespan`.`last_7_days`
    AND `dim_date`.`last_30_days`  = `dim_timespan`.`last_30_days`
    AND `dim_date`.`last_90_days`  = `dim_timespan`.`last_90_days`
    AND `dim_date`.`last_365_days` = `dim_timespan`.`last_365_days`
  GROUP BY
    `dim_timespan`.`dim_timespan_id`,
    `fact_job`.`dim_cluster_id`,
    `fact_job`.`dim_queue_id`,
    `fact_job`.`dim_user_id`,
    `fact_job`.`dim_group_id`,
    `fact_job`.`dim_tags_id`,
    `fact_job`.`dim_cpus_id`;
END//

DELIMITER ;
