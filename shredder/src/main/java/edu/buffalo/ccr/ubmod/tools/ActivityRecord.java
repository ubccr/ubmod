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
 * ActivityRecord.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.tools;

import java.math.BigInteger;

/**
 * This class encapsulates an activity record which is a aggregation of jobs
 * over a given time interval. XXX This class needs re-factoring.
 */
public class ActivityRecord {
    private String host;
    private String queue;
    private String user;
    private String group;
    private Integer jobs;
    private BigInteger wallt;
    private BigInteger avgWallt;
    private BigInteger maxWallt;
    private BigInteger cput;
    private BigInteger avgCput;
    private BigInteger maxCput;
    private Integer avgMem;
    private Integer maxMem;
    private Integer avgVmem;
    private Integer maxVmem;
    private BigInteger avgWait;
    private BigInteger avgExect;
    private Integer avgNodes;
    private Integer maxNodes;
    private Integer avgCpus;
    private Integer maxCpus;
    private Integer userCount;
    private Integer groupCount;

    public ActivityRecord() {
        this.jobs = new Integer(0);
        this.wallt = BigInteger.valueOf(0);
        this.avgWallt = BigInteger.valueOf(0);
        this.maxWallt = BigInteger.valueOf(0);
        this.cput = BigInteger.valueOf(0);
        this.avgCput = BigInteger.valueOf(0);
        this.maxCput = BigInteger.valueOf(0);
        this.avgMem = new Integer(0);
        this.maxMem = new Integer(0);
        this.avgVmem = new Integer(0);
        this.maxVmem = new Integer(0);
        this.avgWait = BigInteger.valueOf(0);
        this.avgExect = BigInteger.valueOf(0);
        this.avgNodes = new Integer(0);
        this.maxNodes = new Integer(0);
        this.avgCpus = new Integer(0);
        this.maxCpus = new Integer(0);
        this.userCount = new Integer(0);
        this.groupCount = new Integer(0);
    }

    public Integer getGroupCount() {
        return groupCount;
    }

    public void setGroupCount(Integer groupCount) {
        this.groupCount = groupCount;
    }

    public Integer getUserCount() {
        return userCount;
    }

    public void setUserCount(Integer userCount) {
        this.userCount = userCount;
    }

    public Integer getAvgCpus() {
        return avgCpus;
    }

    public void setAvgCpus(Integer avgCpus) {
        this.avgCpus = avgCpus;
    }

    public BigInteger getAvgCput() {
        return avgCput;
    }

    public void setAvgCput(BigInteger avgCput) {
        this.avgCput = avgCput;
    }

    public BigInteger getAvgExect() {
        return avgExect;
    }

    public void setAvgExect(BigInteger avgExect) {
        this.avgExect = avgExect;
    }

    public Integer getAvgMem() {
        return avgMem;
    }

    public void setAvgMem(Integer avgMem) {
        this.avgMem = avgMem;
    }

    public Integer getAvgNodes() {
        return avgNodes;
    }

    public void setAvgNodes(Integer avgNodes) {
        this.avgNodes = avgNodes;
    }

    public Integer getAvgVmem() {
        return avgVmem;
    }

    public void setAvgVmem(Integer avgVmem) {
        this.avgVmem = avgVmem;
    }

    public BigInteger getAvgWait() {
        return avgWait;
    }

    public void setAvgWait(BigInteger avgWait) {
        this.avgWait = avgWait;
    }

    public BigInteger getAvgWallt() {
        return avgWallt;
    }

    public void setAvgWallt(BigInteger avgWallt) {
        this.avgWallt = avgWallt;
    }

    public BigInteger getCput() {
        return cput;
    }

    public void setCput(BigInteger cput) {
        this.cput = cput;
    }

    public Integer getJobs() {
        return jobs;
    }

    public void setJobs(Integer jobs) {
        this.jobs = jobs;
    }

    public Integer getMaxCpus() {
        return maxCpus;
    }

    public void setMaxCpus(Integer maxCpus) {
        this.maxCpus = maxCpus;
    }

    public BigInteger getMaxCput() {
        return maxCput;
    }

    public void setMaxCput(BigInteger maxCput) {
        this.maxCput = maxCput;
    }

    public Integer getMaxMem() {
        return maxMem;
    }

    public void setMaxMem(Integer maxMem) {
        this.maxMem = maxMem;
    }

    public Integer getMaxNodes() {
        return maxNodes;
    }

    public void setMaxNodes(Integer maxNodes) {
        this.maxNodes = maxNodes;
    }

    public Integer getMaxVmem() {
        return maxVmem;
    }

    public void setMaxVmem(Integer maxVmem) {
        this.maxVmem = maxVmem;
    }

    public BigInteger getMaxWallt() {
        return maxWallt;
    }

    public void setMaxWallt(BigInteger maxWallt) {
        this.maxWallt = maxWallt;
    }

    public BigInteger getWallt() {
        return wallt;
    }

    public void setWallt(BigInteger wallt) {
        this.wallt = wallt;
    }

    public String getHost() {
        return host;
    }

    public void setHost(String host) {
        this.host = host;
    }

    public String getQueue() {
        return queue;
    }

    public void setQueue(String queue) {
        this.queue = queue;
    }

    public String getUser() {
        return user;
    }

    public void setUser(String user) {
        this.user = user;
    }

    public String getGroup() {
        return group;
    }

    public void setGroup(String group) {
        this.group = group;
    }

}
