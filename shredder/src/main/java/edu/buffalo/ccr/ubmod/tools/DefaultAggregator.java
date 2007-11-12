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
 * ------------------------------------
 * DefaultAggregator.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.tools;

import java.math.BigInteger;
import java.util.Calendar;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.springframework.context.ApplicationContext;
import org.springframework.context.support.ClassPathXmlApplicationContext;
import org.springframework.orm.ibatis.support.SqlMapClientDaoSupport;

/**
 * Default Aggregator class. This class desperately needs refactoring. Consider
 * doing more of this work at the database level.
 */
public class DefaultAggregator extends SqlMapClientDaoSupport implements
        Aggregator {
    private static Log logger = LogFactory.getLog(DefaultAggregator.class);

    public static void main(String[] args) {
        ApplicationContext context = new ClassPathXmlApplicationContext(
                new String[] { "beans.xml" });
        Aggregator a = (Aggregator) context.getBean("aggregator");
        try {
            a.aggregate();
        } catch(AggregatorException e) {
            System.err.println("Fatal error: " + e.getMessage());
        }
    }

    public void aggregate() throws AggregatorException {
        Map<String, Map> clusterMap = updateClusters();
        Map<String, Map> qMap = updateQueues(clusterMap);
        Map<String, Map> groupMap = updateGroups(clusterMap);
        Map<String, Map> userMap = updateUsers(clusterMap, groupMap, qMap);

        // XXX always start intervals from previous day. Probably want to
        // re-factor this to a configurable parameter
        Calendar endDate = Calendar.getInstance();
        endDate.add(Calendar.DATE, -1);

        Map<String, Map> imap = updateIntervals(endDate);
        this.getSqlMapClientTemplate().delete("activity.truncateActivity");
        updateClusterActivity(clusterMap, imap);
        updateQueueActivity(qMap, clusterMap, imap);
        updateGroupActivity(groupMap, clusterMap, imap);
        updateUserActivity(userMap, clusterMap, imap);

        updateCpuConsumption(clusterMap, imap);
        updateActualWaitTime(clusterMap, imap);
    }

    @SuppressWarnings("unchecked")
    private void updateClusterActivity(Map<String, Map> clusterMap,
            Map<String, Map> imap) {
        this.getSqlMapClientTemplate().delete("cluster.truncateActivity");
        for(Map interval : imap.values()) {
            List<ActivityRecord> records = (List<ActivityRecord>) this
                    .getSqlMapClientTemplate().queryForList(
                            "cluster.selectActivity", interval);

            for(ActivityRecord rec : records) {
                Long aid = (Long) this.getSqlMapClientTemplate().insert(
                        "activity.insertActivity", rec);
                Map cluster = getFromMapOrError(clusterMap, rec.getHost());
                if(cluster == null) {
                    logger.fatal("Key not found skipping record.");
                    continue;
                }

                Map<String, Object> params = new HashMap<String, Object>();
                params.put("user_count", rec.getUserCount());
                params.put("group_count", rec.getGroupCount());
                params.put("interval_id", interval.get("interval_id"));
                params.put("cluster_id", cluster.get("cluster_id"));
                params.put("activity_id", aid);
                this.getSqlMapClientTemplate().insert("cluster.insertActivity",
                        params);
            }
        }
    }

    private Map getFromMapOrError(Map<String, Map> map, String key) {
        Map val = (Map) map.get(key);
        if(val == null) {
            logger
                    .fatal("Failed to find key: '"
                            + key
                            + "' in map. This was assumed to already have been added to the database.");
        }
        return val;
    }

    @SuppressWarnings("unchecked")
    private void updateQueueActivity(Map<String, Map> qMap,
            Map<String, Map> clusterMap, Map<String, Map> imap) {
        this.getSqlMapClientTemplate().delete("queue.truncateActivity");
        for(Map interval : imap.values()) {
            List<ActivityRecord> records = (List<ActivityRecord>) this
                    .getSqlMapClientTemplate().queryForList(
                            "queue.selectActivity", interval);

            for(ActivityRecord rec : records) {
                Long aid = (Long) this.getSqlMapClientTemplate().insert(
                        "activity.insertActivity", rec);
                Map cluster = getFromMapOrError(clusterMap, rec.getHost());
                Map queue = getFromMapOrError(qMap, rec.getQueue());
                if(cluster == null || queue == null) {
                    logger.fatal("Key not found skipping record.");
                    continue;
                }

                Map<String, Object> params = new HashMap<String, Object>();
                params.put("user_count", rec.getUserCount());
                params.put("group_count", rec.getGroupCount());
                params.put("interval_id", interval.get("interval_id"));
                params.put("cluster_id", cluster.get("cluster_id"));
                params.put("queue_id", queue.get("queue_id"));
                params.put("activity_id", aid);
                this.getSqlMapClientTemplate().insert("queue.insertActivity",
                        params);
            }
        }
    }

    @SuppressWarnings("unchecked")
    private void updateGroupActivity(Map<String, Map> groupMap,
            Map<String, Map> clusterMap, Map<String, Map> imap) {
        this.getSqlMapClientTemplate().delete("group.truncateActivity");
        for(Map interval : imap.values()) {
            List<ActivityRecord> records = (List<ActivityRecord>) this
                    .getSqlMapClientTemplate().queryForList(
                            "group.selectActivity", interval);

            for(ActivityRecord rec : records) {
                Long aid = (Long) this.getSqlMapClientTemplate().insert(
                        "activity.insertActivity", rec);
                Map cluster = getFromMapOrError(clusterMap, rec.getHost());
                Map group = getFromMapOrError(groupMap, rec.getGroup());
                if(cluster == null || group == null) {
                    logger.fatal("Key not found skipping record.");
                    continue;
                }

                Map<String, Object> params = new HashMap<String, Object>();
                params.put("user_count", rec.getUserCount());
                params.put("interval_id", interval.get("interval_id"));
                params.put("cluster_id", cluster.get("cluster_id"));
                params.put("group_id", group.get("group_id"));
                params.put("activity_id", aid);
                this.getSqlMapClientTemplate().insert("group.insertActivity",
                        params);
            }
        }
    }

    @SuppressWarnings("unchecked")
    private void updateUserActivity(Map<String, Map> userMap,
            Map<String, Map> clusterMap, Map<String, Map> imap) {
        this.getSqlMapClientTemplate().delete("user.truncateActivity");
        for(Map interval : imap.values()) {
            List<ActivityRecord> records = (List<ActivityRecord>) this
                    .getSqlMapClientTemplate().queryForList(
                            "user.selectActivity", interval);

            for(ActivityRecord rec : records) {
                Long aid = (Long) this.getSqlMapClientTemplate().insert(
                        "activity.insertActivity", rec);
                Map cluster = getFromMapOrError(clusterMap, rec.getHost());
                Map user = getFromMapOrError(userMap, rec.getUser());
                if(cluster == null || user == null) {
                    logger.fatal("Key not found skipping record.");
                    continue;
                }

                Map<String, Object> params = new HashMap<String, Object>();
                params.put("interval_id", interval.get("interval_id"));
                params.put("cluster_id", cluster.get("cluster_id"));
                params.put("user_id", user.get("user_id"));
                params.put("activity_id", aid);
                this.getSqlMapClientTemplate().insert("user.insertActivity",
                        params);
            }
        }
    }

    private Map<String, Map> makeMap(List<Map> list, String key) {
        Map<String, Map> map = new HashMap<String, Map>();
        if(list != null) {
            for(Map item : list) {
                map.put((String) item.get(key), item);
            }
        }
        return map;
    }

    @SuppressWarnings("unchecked")
    private Map<String, Map> updateClusters() {
        List<Map> clusterList = this.getSqlMapClientTemplate().queryForList(
                "cluster.selectAll");
        Map<String, Map> clusterMap = this.makeMap(clusterList, "host");

        List<Map> rows = this.getSqlMapClientTemplate().queryForList(
                "cluster.selectFromEvent");
        for(Map r : rows) {
            String host = (String) r.get("host");
            Map cluster = (Map) clusterMap.get(host);

            if(cluster == null) {
                logger.info("Adding new cluster: " + host);
                Long id = (Long) this.getSqlMapClientTemplate().insert(
                        "cluster.insert", host);
                r.put("cluster_id", id);
                clusterMap.put(host, r);
                logger.info("Successfully inserted new cluser with id: " + id);
            } else {
                logger.info("Cluster '" + host + "' already exists");
            }
        }
        return clusterMap;
    }

    @SuppressWarnings("unchecked")
    private Map<String, Map> updateQueues(Map<String, Map> clusterMap) {
        List<Map> qList = this.getSqlMapClientTemplate().queryForList(
                "queue.selectAll");
        Map<String, Map> qMap = this.makeMap(qList, "queue");

        List<Map> rows = this.getSqlMapClientTemplate().queryForList(
                "queue.selectFromEvent");
        for(Map r : rows) {
            String qName = (String) r.get("queue");
            Map queue = (Map) qMap.get(qName);

            if(queue == null) {
                logger.info("Adding new queue: " + qName);
                Long id = (Long) this.getSqlMapClientTemplate().insert(
                        "queue.insert", qName);
                r.put("queue_id", id);
                queue = r;
                logger.info("Successfully inserted new queue with id: " + id);
            } else {
                logger.info("Queue '" + qName + "' already exists");
            }

            Map cluster = (Map) clusterMap.get((String) r.get("host"));
            if(cluster != null) {
                Map<String, Object> params = new HashMap<String, Object>();
                params.put("queueId", queue.get("queue_id"));
                params.put("clusterId", cluster.get("cluster_id"));
                this.getSqlMapClientTemplate().delete(
                        "queue.deleteClusterLink", params);
                this.getSqlMapClientTemplate().insert(
                        "queue.insertClusterLink", params);
            }

            qMap.put(qName, queue);
        }
        return qMap;
    }

    @SuppressWarnings("unchecked")
    private Map<String, Map> updateGroups(Map<String, Map> clusterMap) {
        List<Map> groupList = this.getSqlMapClientTemplate().queryForList(
                "group.selectAll");
        Map<String, Map> groupMap = this.makeMap(groupList, "group_name");

        List<Map> rows = this.getSqlMapClientTemplate().queryForList(
                "group.selectFromEvent");
        for(Map r : rows) {
            String groupName = (String) r.get("ugroup");
            Map group = (Map) groupMap.get(groupName);

            if(group == null) {
                logger.info("Adding new group: " + groupName);
                Long id = (Long) this.getSqlMapClientTemplate().insert(
                        "group.insert", groupName);
                r.put("group_id", id);
                group = r;
                logger.info("Successfully inserted new group with id: " + id);
            } else {
                logger.info("Group '" + groupName + "' already exists");
            }

            Map cluster = (Map) clusterMap.get((String) r.get("host"));
            if(cluster != null) {
                Map<String, Object> params = new HashMap<String, Object>();
                params.put("groupId", group.get("group_id"));
                params.put("clusterId", cluster.get("cluster_id"));
                this.getSqlMapClientTemplate().delete(
                        "group.deleteClusterLink", params);
                this.getSqlMapClientTemplate().insert(
                        "group.insertClusterLink", params);
            }
            groupMap.put(groupName, group);
        }
        return groupMap;
    }

    @SuppressWarnings("unchecked")
    private Map<String, Map> updateUsers(Map<String, Map> clusterMap,
            Map<String, Map> groupMap, Map<String, Map> qMap) {
        List<Map> userList = this.getSqlMapClientTemplate().queryForList(
                "user.selectAll");
        Map<String, Map> userMap = this.makeMap(userList, "user");

        List<Map> rows = this.getSqlMapClientTemplate().queryForList(
                "user.selectFromEvent");
        for(Map r : rows) {
            String userName = (String) r.get("user");
            Map user = (Map) userMap.get(userName);

            if(user == null) {
                logger.info("Adding new user: " + userName);
                Long id = (Long) this.getSqlMapClientTemplate().insert(
                        "user.insert", userName);
                r.put("user_id", id);
                user = r;
                logger.info("Successfully inserted new user with id: " + id);
            } else {
                logger.info("User '" + userName + "' already exists");
            }

            Map cluster = (Map) clusterMap.get((String) r.get("host"));
            Map group = (Map) groupMap.get((String) r.get("ugroup"));
            Map queue = (Map) qMap.get((String) r.get("queue"));

            Map<String, Object> params = new HashMap<String, Object>();
            params.put("userId", user.get("user_id"));

            if(cluster != null) {
                params.put("clusterId", cluster.get("cluster_id"));

                this.getSqlMapClientTemplate().delete("user.deleteClusterLink",
                        params);
                this.getSqlMapClientTemplate().insert("user.insertClusterLink",
                        params);
            }

            if(group != null) {
                params.put("groupId", group.get("group_id"));

                this.getSqlMapClientTemplate().delete("user.deleteGroupLink",
                        params);
                this.getSqlMapClientTemplate().insert("user.insertGroupLink",
                        params);
            }

            if(queue != null) {
                params.put("queueId", queue.get("queue_id"));
                this.getSqlMapClientTemplate().delete("user.deleteQueueLink",
                        params);
                this.getSqlMapClientTemplate().insert("user.insertQueueLink",
                        params);
            }

            userMap.put(userName, user);
        }
        return userMap;
    }

    private Date getIntervalDate(Calendar endDate, int days) {
        Calendar c = Calendar.getInstance();
        c.setTime(endDate.getTime());
        c.set(Calendar.HOUR_OF_DAY, 23);
        c.set(Calendar.MINUTE, 59);
        c.set(Calendar.SECOND, 59);
        c.add(Calendar.DATE, days);
        c.set(Calendar.HOUR_OF_DAY, 0);
        c.set(Calendar.MINUTE, 0);
        c.set(Calendar.SECOND, 0);
        return c.getTime();
    }

    private Map<String, Object> addInterval(String label, Date start, Date end) {
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("label", label);
        map.put("start", start);
        map.put("end", end);
        Long id = (Long) this.getSqlMapClientTemplate().insert(
                "interval.insert", map);
        map.put("interval_id", id);
        return map;
    }

    private Map<String, Map> updateIntervals(Calendar endDate) {
        this.getSqlMapClientTemplate().delete("interval.truncate");
        Date end = endDate.getTime();

        Object[][] labels = new Object[][] { { "Week", -7 }, { "Month", -30 },
                { "Quarter", -84 }, { "Year", -365 } };

        Map<String, Map> imap = new HashMap<String, Map>();
        for(Object[] o : labels) {
            String label = (String) o[0];
            Integer days = (Integer) o[1];
            Map<String, Object> interval = addInterval(label, getIntervalDate(
                    endDate, days), end);
            imap.put(label, interval);
        }
        return imap;
    }

    @SuppressWarnings("unchecked")
    private void updateCpuConsumption(Map<String, Map> clusterMap,
            Map<String, Map> imap) {
        this.getSqlMapClientTemplate()
                .delete("activity.truncateCpuConsumption");

        for(Map cluster : clusterMap.values()) {
            for(Map interval : imap.values()) {
                int[][] cpus = this.getCpuMinMax();
                for(int c = 0; c < cpus.length; c++) {
                    int min = cpus[c][0];
                    int max = cpus[c][1];
                    Map<String, Object> params = new HashMap<String, Object>();
                    params.put("host", cluster.get("host"));
                    params.put("start", interval.get("start"));
                    params.put("end", interval.get("end"));
                    if(min != -1) {
                        params.put("max", new Integer(max));
                        params.put("min", new Integer(min));
                    } else {
                        params.put("limit", new Integer(max));
                    }

                    Map rec = (Map) this.getSqlMapClientTemplate()
                            .queryForObject("activity.cpuConsumption", params);
                    if(rec == null) {
                        logger.warn("No cput found for cpus " + min + "-" + max
                                + " for time period " + interval.get("start")
                                + "-" + interval.get("end") + " for cluster "
                                + cluster.get("host"));
                        rec = new HashMap();
                        rec.put("cput", BigInteger.valueOf(0));
                    }

                    rec.put("interval_id", interval.get("interval_id"));
                    rec.put("cluster_id", cluster.get("cluster_id"));
                    String label = null;
                    if(min == -1) {
                        label = ">" + max;
                    } else if(min != max) {
                        label = min + "-" + max;
                    } else {
                        label = max + "";
                    }
                    rec.put("label", label);
                    rec.put("view_order", new Integer(c));
                    this.getSqlMapClientTemplate().insert(
                            "activity.insertCpuConsumption", rec);
                }

            }
        }
    }

    @SuppressWarnings("unchecked")
    private void updateActualWaitTime(Map<String, Map> clusterMap,
            Map<String, Map> imap) {
        this.getSqlMapClientTemplate()
                .delete("activity.truncateActualWaitTime");

        for(Map cluster : clusterMap.values()) {
            for(Map interval : imap.values()) {
                int[][] cpus = this.getCpuMinMax();
                for(int c = 0; c < cpus.length; c++) {
                    int min = cpus[c][0];
                    int max = cpus[c][1];
                    Map<String, Object> params = new HashMap<String, Object>();
                    params.put("host", cluster.get("host"));
                    params.put("start", interval.get("start"));
                    params.put("end", interval.get("end"));
                    if(min != -1) {
                        params.put("max", new Integer(max));
                        params.put("min", new Integer(min));
                    } else {
                        params.put("limit", new Integer(max));
                    }

                    HashMap rec = (HashMap) this.getSqlMapClientTemplate()
                            .queryForObject("activity.actualWaitTime", params);
                    if(rec == null) {
                        logger.warn("No avg_wait found for cpus " + min + "-"
                                + max + " for time period "
                                + interval.get("start") + "-"
                                + interval.get("end") + " for cluster "
                                + cluster.get("host"));
                        rec = new HashMap();
                        rec.put("avg_wait", BigInteger.valueOf(0));
                    }

                    rec.put("interval_id", interval.get("interval_id"));
                    rec.put("cluster_id", cluster.get("cluster_id"));
                    String label = null;
                    if(min == -1) {
                        label = ">" + max;
                    } else if(min != max) {
                        label = min + "-" + max;
                    } else {
                        label = max + "";
                    }
                    rec.put("label", label);
                    rec.put("view_order", new Integer(c));
                    this.getSqlMapClientTemplate().insert(
                            "activity.insertActualWaitTime", rec);
                }
            }
        }
    }

    private int[][] getCpuMinMax() {
        int[][] a = { { 1, 1 }, { 2, 2 }, { 3, 4 }, { 5, 8 }, { 9, 16 },
                { 17, 32 }, { 33, 64 }, { 65, 128 }, { 129, 256 },
                { 257, 512 }, { -1, 512 } };
        return a;
    }

}
