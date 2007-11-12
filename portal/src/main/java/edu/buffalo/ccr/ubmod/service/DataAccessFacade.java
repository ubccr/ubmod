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
 * DataAccessFacade.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.service;

import edu.buffalo.ccr.ubmod.dao.ChartDao;
import edu.buffalo.ccr.ubmod.dao.ClusterDao;
import edu.buffalo.ccr.ubmod.dao.GroupDao;
import edu.buffalo.ccr.ubmod.dao.LookupTableDao;
import edu.buffalo.ccr.ubmod.dao.QueueDao;
import edu.buffalo.ccr.ubmod.dao.UserDao;

/**
 * Facade for all Data Access Objects used by the application
 */
public class DataAccessFacade {
    private ChartDao chartDao;
    private ClusterDao clusterDao;
    private GroupDao groupDao;
    private LookupTableDao lookupTableDao;
    private QueueDao queueDao;
    private UserDao userDao;

    public ChartDao getChartDao() {
        return chartDao;
    }

    public void setChartDao(ChartDao chartDao) {
        this.chartDao = chartDao;
    }

    public ClusterDao getClusterDao() {
        return clusterDao;
    }

    public void setClusterDao(ClusterDao clusterDao) {
        this.clusterDao = clusterDao;
    }

    public GroupDao getGroupDao() {
        return groupDao;
    }

    public void setGroupDao(GroupDao groupDao) {
        this.groupDao = groupDao;
    }

    public LookupTableDao getLookupTableDao() {
        return lookupTableDao;
    }

    public void setLookupTableDao(LookupTableDao lookupTableDao) {
        this.lookupTableDao = lookupTableDao;
    }

    public QueueDao getQueueDao() {
        return queueDao;
    }

    public void setQueueDao(QueueDao queueDao) {
        this.queueDao = queueDao;
    }

    public UserDao getUserDao() {
        return userDao;
    }

    public void setUserDao(UserDao userDao) {
        this.userDao = userDao;
    }

}
