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
 * DefaultEventRecord.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.tools;

import java.util.Date;

/**
 * This class encapsulates an event record in a Log file to be loaded into the
 * database. The properties are very much based off the PBS accounting log file
 * format. In the future this should be more generalized to work with other
 * resource managers
 */
public class DefaultEventRecord {
    private Date dateKey;
    private Long jobId;
    private Long jobArrayIndex;
    private String host;
    private EventType eventType;
    private String user;
    private String group;
    private String queue;

    private String exitStatus;
    private String session;
    private String requestor;
    private String jobname;
    private String account;
    private String execHost;

    // unix times
    private Long creationTime;
    private Long queueTime;
    private Long eligibleTime;
    private Long startTime;
    private Long endTime;

    // count values
    private Integer resourcesUsedNodes;
    private Integer resourcesUsedCpus;
    private String resourceListNodes;
    private String resourceListProcs;
    private String resourceListNeednodes;

    private String resourceListNcpus;
    private String resourceListNodect;

    // times in seconds
    private Long resourcesUsedWalltime;
    private Long resourcesUsedCput;
    private Long resourceListPcput;
    private Long resourceListCput;
    private Long resourceListWalltime;

    // memory in KB
    private Long resourcesUsedVmem;
    private Long resourcesUsedMem;
    private Long resourceListMem;
    private Long resourceListPmem;

    /**
     * To be recorded in accounting
     */
    public String getAccount() {
        return account;
    }

    public void setAccount(String account) {
        this.account = account;
    }

    /**
     * Time at which the resources reservation got created
     */
    public Long getCreationTime() {
        return creationTime;
    }

    public void setCreationTime(Long creationTime) {
        this.creationTime = creationTime;
    }

    /**
     * Date which the event record was actually created and written to the log
     * file
     */
    public Date getDateKey() {
        return dateKey;
    }

    public void setDateKey(Date dateKey) {
        this.dateKey = dateKey;
    }

    /**
     * Time when the job because eligible to run
     */
    public Long getEligibleTime() {
        return eligibleTime;
    }

    public void setEligibleTime(Long eligibleTime) {
        this.eligibleTime = eligibleTime;
    }

    /**
     * Name of the host on which the job was executed
     */
    public String getExecHost() {
        return execHost;
    }

    public void setExecHost(String execHost) {
        this.execHost = execHost;
    }

    /**
     * The exit status of the job
     */
    public String getExitStatus() {
        return exitStatus;
    }

    public void setExitStatus(String exitStatus) {
        this.exitStatus = exitStatus;
    }

    /**
     * The group under which the job was executed
     */
    public String getGroup() {
        return group;
    }

    public void setGroup(String group) {
        this.group = group;
    }

    /**
     * Host name of the machine which is running the resource manager
     */
    public String getHost() {
        return host;
    }

    public void setHost(String host) {
        this.host = host;
    }

    /**
     * Unique id of the job
     */
    public Long getJobId() {
        return jobId;
    }

    public void setJobId(Long jobId) {
        this.jobId = jobId;
    }

    /**
     * Job array index - only used in job arrays
     */
    public Long getJobArrayIndex() {
        return jobArrayIndex;
    }

    public void setJobArrayIndex(Long jobArrayIndex) {
        this. jobArrayIndex =  jobArrayIndex;
    }

    /**
     * The name of the job
     */
    public String getJobname() {
        return jobname;
    }

    public void setJobname(String jobname) {
        this.jobname = jobname;
    }

    /**
     * Time when the job ended execution
     */
    public Long getEndTime() {
        return endTime;
    }

    public void setEndTime(Long endTime) {
        this.endTime = endTime;
    }

    /**
     * Time when the job started execution
     */
    public Long getStartTime() {
        return startTime;
    }

    public void setStartTime(Long startTime) {
        this.startTime = startTime;
    }

    /**
     * The name of the queue which the job exectued
     */
    public String getQueue() {
        return queue;
    }

    public void setQueue(String queue) {
        this.queue = queue;
    }

    /**
     * The time when the job was queued into the current queue
     */
    public Long getQueueTime() {
        return queueTime;
    }

    public void setQueueTime(Long queueTime) {
        this.queueTime = queueTime;
    }

    public String getRequestor() {
        return requestor;
    }

    public void setRequestor(String requestor) {
        this.requestor = requestor;
    }

    public Long getResourceListCput() {
        return resourceListCput;
    }

    public void setResourceListCput(Long resourceListCput) {
        this.resourceListCput = resourceListCput;
    }

    public Long getResourceListMem() {
        return resourceListMem;
    }

    public void setResourceListMem(Long resourceListMem) {
        this.resourceListMem = resourceListMem;
    }

    public String getResourceListNcpus() {
        return resourceListNcpus;
    }

    public void setResourceListNcpus(String resourceListNcpus) {
        this.resourceListNcpus = resourceListNcpus;
    }

    public String getResourceListNeednodes() {
        return resourceListNeednodes;
    }

    public void setResourceListNeednodes(String resourceListNeednodes) {
        this.resourceListNeednodes = resourceListNeednodes;
    }

    public String getResourceListNodect() {
        return resourceListNodect;
    }

    public void setResourceListNodect(String resourceListNodect) {
        this.resourceListNodect = resourceListNodect;
    }

    public String getResourceListNodes() {
        return resourceListNodes;
    }

    public void setResourceListNodes(String resourceListNodes) {
        this.resourceListNodes = resourceListNodes;
    }

    public Long getResourceListPcput() {
        return resourceListPcput;
    }

    public void setResourceListPcput(Long resourceListPcput) {
        this.resourceListPcput = resourceListPcput;
    }

    public Long getResourceListPmem() {
        return resourceListPmem;
    }

    public void setResourceListPmem(Long resourceListPmem) {
        this.resourceListPmem = resourceListPmem;
    }

    public String getResourceListProcs() {
        return resourceListProcs;
    }

    public void setResourceListProcs(String resourceListProcs) {
        this.resourceListProcs = resourceListProcs;
    }

    public Long getResourceListWalltime() {
        return resourceListWalltime;
    }

    public void setResourceListWalltime(Long resourceListWalltime) {
        this.resourceListWalltime = resourceListWalltime;
    }

    public Integer getResourcesUsedCpus() {
        return resourcesUsedCpus;
    }

    public void setResourcesUsedCpus(Integer resourcesUsedCpus) {
        this.resourcesUsedCpus = resourcesUsedCpus;
    }

    public Long getResourcesUsedCput() {
        return resourcesUsedCput;
    }

    public void setResourcesUsedCput(Long resourcesUsedCput) {
        this.resourcesUsedCput = resourcesUsedCput;
    }

    public Long getResourcesUsedMem() {
        return resourcesUsedMem;
    }

    public void setResourcesUsedMem(Long resourcesUsedMem) {
        this.resourcesUsedMem = resourcesUsedMem;
    }

    public Integer getResourcesUsedNodes() {
        return resourcesUsedNodes;
    }

    public void setResourcesUsedNodes(Integer resourcesUsedNodes) {
        this.resourcesUsedNodes = resourcesUsedNodes;
    }

    public Long getResourcesUsedVmem() {
        return resourcesUsedVmem;
    }

    public void setResourcesUsedVmem(Long resourcesUsedVmem) {
        this.resourcesUsedVmem = resourcesUsedVmem;
    }

    public Long getResourcesUsedWalltime() {
        return resourcesUsedWalltime;
    }

    public void setResourcesUsedWalltime(Long resourcesUsedWalltime) {
        this.resourcesUsedWalltime = resourcesUsedWalltime;
    }

    public String getSession() {
        return session;
    }

    public void setSession(String session) {
        this.session = session;
    }

    /**
     * Type of event this record represents
     */
    public EventType getEventType() {
        return eventType;
    }

    public String getEventTypeString() {
        if(eventType != null) {
            return eventType.toString();
        } else {
            return null;
        }
    }

    public void setEventType(EventType type) {
        this.eventType = type;
    }

    /**
     * Username under which the job executed
     * 
     * @return
     */
    public String getUser() {
        return user;
    }

    public void setUser(String user) {
        this.user = user;
    }
}
