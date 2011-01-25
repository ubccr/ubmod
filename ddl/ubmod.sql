/*
 * The contents of this file are subject to the University at Buffalo Public
 * License Version 1.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.ccr.buffalo.edu/licenses/ubpl.txt
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 (the "GPL"), or the GNU
 * Lesser General Public License Version 2.1 (the "LGPL"), in which case the
 * provisions of the GPL or the LGPL are applicable instead of those above. If
 * you wish to allow use of your version of this file only under the terms of
 * either the GPL or the LGPL, and not to allow others to use your version of
 * this file under the terms of the UBPL, indicate your decision by deleting
 * the provisions above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the provisions above,
 * a recipient may use your version of this file under the terms of any one of
 * the UBPL, the GPL or the LGPL.
 *
 * ====================================
 * ubmod.sql
 * ====================================
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 *
 */
 drop table if exists event ;
 drop table if exists research_group ;
 drop table if exists user ;
 drop table if exists host_log ;
 drop table if exists time_interval ;
 drop table if exists cluster ;
 drop table if exists activity ;
 drop table if exists cluster_activity ;
 drop table if exists user_activity ;
 drop table if exists group_activity ;
 drop table if exists queue ;
 drop table if exists queue_activity ;
 drop table if exists queue_cluster ;
 drop table if exists user_cluster ;
 drop table if exists group_cluster ;
 drop table if exists user_group ;
 drop table if exists user_queue ;
 drop table if exists cpu_consumption ;
 drop table if exists actual_wait_time ;


-- event
create table event (
  event_id                   bigint unsigned auto_increment not null,
  date_key                  datetime,
  job_id                    int unsigned not null,
  job_array_index           int unsigned,
  host                      varchar(255) not null,
  queue                     varchar(255),
  type                      char(1) not null,
  user                      varchar(255),
  ugroup                    varchar(255),
  ctime                     datetime,
  qtime                     datetime,
  start                     datetime,
  end                       datetime,
  etime                     datetime,
  exit_status               int,
  session                   int unsigned,
  requestor                 varchar(255),
  jobname                   varchar(255),
  account                   varchar(255),
  exec_host                 text,
  resources_used_vmem       bigint unsigned,
  resources_used_mem        bigint unsigned,
  resources_used_walltime   bigint unsigned,
  resources_used_nodes      int unsigned,
  resources_used_cpus       int unsigned,
  resources_used_cput       bigint unsigned,
  resource_list_nodes       text,
  resource_list_procs       text,
  resource_list_neednodes   text,
  resource_list_pcput       bigint unsigned,
  resource_list_cput        bigint unsigned,
  resource_list_walltime    bigint unsigned,
  resource_list_ncpus       tinyint unsigned,
  resource_list_nodect      int unsigned,
  resource_list_mem         bigint unsigned,
  resource_list_pmem        bigint unsigned,
  constraint pk_Event primary key (event_id)
) ;

-- research_group
create table research_group (
  group_id                   int unsigned auto_increment not null,
  group_name                varchar(255) not null,
  pi_name                   varchar(255),
  constraint pk_Research_group primary key (group_id)
) ;

-- user
create table user (
  user_id                    int unsigned auto_increment not null,
  user                      varchar(255) not null,
  display_name              varchar(255),
  constraint pk_User primary key (user_id)
) ;

-- host_log
create table host_log (
  event_id                  bigint unsigned not null,
  host                      varchar(255) not null,
  cpu                       tinyint unsigned not null,
  constraint pk_Host_log primary key (event_id,host,cpu)
) ;

-- time_interval
create table time_interval (
  interval_id                int unsigned auto_increment not null,
  time_interval             varchar(255) not null,
  start                     datetime not null,
  end                       datetime not null,
  constraint pk_Time_interval primary key (interval_id)
) ;

-- cluster
create table cluster (
  cluster_id                 int unsigned auto_increment not null,
  host                      varchar(255) not null,
  display_name              varchar(255),
  constraint pk_Cluster primary key (cluster_id)
) ;

-- activity
create table activity (
  activity_id                int unsigned auto_increment not null,
  jobs                      int unsigned,
  wallt                     bigint unsigned,
  avg_wallt                 bigint unsigned,
  max_wallt                 bigint unsigned,
  cput                      bigint unsigned,
  avg_cput                  bigint unsigned,
  max_cput                  bigint unsigned,
  avg_mem                   int unsigned,
  max_mem                   int unsigned,
  avg_vmem                  int unsigned,
  max_vmem                  int unsigned,
  avg_wait                  bigint unsigned,
  avg_exect                 bigint unsigned,
  avg_nodes                 int unsigned,
  max_nodes                 int unsigned,
  avg_cpus                  int unsigned,
  max_cpus                  int unsigned,
  constraint pk_Activity primary key (activity_id)
) ;

-- cluster_activity
create table cluster_activity (
  cluster_id                int unsigned not null,
  activity_id               int unsigned not null,
  interval_id               int unsigned not null,
  user_count                int unsigned,
  group_count               int unsigned,
  constraint pk_Cluster_activity primary key (cluster_id,activity_id)
) ;

-- user_activity
create table user_activity (
  user_id                   int unsigned not null,
  activity_id               int unsigned not null,
  cluster_id                int unsigned not null,
  interval_id               int unsigned not null,
  constraint pk_User_activity primary key (user_id,activity_id)
) ;

-- group_activity
create table group_activity (
  group_id                  int unsigned not null,
  activity_id               int unsigned not null,
  cluster_id                int unsigned not null,
  interval_id               int unsigned not null,
  user_count                int unsigned,
  constraint pk_Group_activity primary key (group_id,activity_id)
) ;

-- queue
create table queue (
  queue_id                   int unsigned auto_increment not null,
  queue                     varchar(255) not null,
  constraint pk_Queue primary key (queue_id)
) ;

-- queue_activity
create table queue_activity (
  queue_id                  int unsigned not null,
  activity_id               int unsigned not null,
  interval_id               int unsigned not null,
  cluster_id                int unsigned not null,
  user_count                int unsigned,
  group_count               int unsigned,
  constraint pk_Queue_activity primary key (queue_id,activity_id)
) ;

-- queue_cluster
create table queue_cluster (
  queue_id                  int unsigned not null,
  cluster_id                int unsigned not null,
  constraint pk_Queue_cluster primary key (queue_id,cluster_id)
) ;

-- user_cluster
create table user_cluster (
  user_id                   int unsigned not null,
  cluster_id                int unsigned not null,
  constraint pk_User_cluster primary key (user_id,cluster_id)
) ;

-- group_cluster
create table group_cluster (
  group_id                  int unsigned not null,
  cluster_id                int unsigned not null,
  constraint pk_Group_cluster primary key (group_id,cluster_id)
) ;

-- user_group
create table user_group (
  user_id                   int unsigned not null,
  group_id                  int unsigned not null,
  constraint pk_User_group primary key (user_id,group_id)
) ;

-- user_queue
create table user_queue (
  user_id                   int unsigned not null,
  queue_id                  int unsigned not null,
  constraint pk_User_queue primary key (user_id,queue_id)
) ;

-- cpu_consumption
create table cpu_consumption (
  cluster_id                int unsigned not null,
  interval_id               int unsigned not null,
  label                     varchar(255) not null,
  cput                      bigint unsigned,
  view_order                tinyint unsigned,
  constraint pk_Cpu_consumption primary key (cluster_id,interval_id,label)
) ;

-- actual_wait_time
create table actual_wait_time (
  cluster_id                int unsigned not null,
  interval_id               int unsigned not null,
  label                     varchar(255) not null,
  avg_wait                  bigint unsigned,
  view_order                tinyint unsigned,
  constraint pk_Actual_wait_time primary key (cluster_id,interval_id,label)
) ;

create index date_key_x on event  (date_key) ;
create index type_x on event  (type) ;
create index job_id_x on event  (job_id) ;
create index queue_x on event  (queue) ;
create index user_x on event  (user) ;
create index group_x on event  (ugroup) ;
create index host_x on event  (host) ;

