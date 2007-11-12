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
 * QueueDao.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.dao;

import java.util.List;
import java.util.Map;

import edu.buffalo.ccr.ubmod.util.DefaultParams;

/**
 * Queue Data Access Object. Provides methods for retrieving data associated
 * with a queue. Jobs can be run in a given queue which can have certain
 * resource limits associated with it. The queue's are harvested from the
 * resource manager log files
 */
public interface QueueDao {

    /**
     * Fetches all queue activity
     * 
     * @param params
     *            default request params
     * @return
     */
    public List<Map> getAllQueueActivity(DefaultParams params);

    /**
     * Fetches the total number of queue activity records. This is used for
     * paging the Javascript grid
     * 
     * @param params
     *            default request params
     * @return The total number of queue activity records
     */
    public Integer getAllQueueActivityCount(DefaultParams params);

    /**
     * Fetch activity for a queue by id
     * 
     * @param queueId
     *            unique id for a queue
     * @param params
     *            default request params
     * @return Map of queue activity data
     * @throws IdNotFoundException
     */
    public Map getQueueActivityById(Integer queueId, DefaultParams params)
            throws IdNotFoundException;
}
