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
 * UserDaoIbatisImpl.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.dao;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.springframework.orm.ibatis.support.SqlMapClientDaoSupport;

import edu.buffalo.ccr.ubmod.util.DefaultParams;

/**
 * iBATIS Implementation for the User Data Access Object Interface
 */
public class UserDaoIbatisImpl extends SqlMapClientDaoSupport implements
        UserDao {

    @SuppressWarnings("unchecked")
    public List<Map> getAllUserActivity(DefaultParams params) {
        return (List<Map>) this.getSqlMapClientTemplate().queryForList(
                "user.activity", this.getActivityFilterMap(params));
    }

    public Integer getAllUserActivityCount(DefaultParams params) {
        return (Integer) this.getSqlMapClientTemplate().queryForObject(
                "user.activityCount", this.getActivityFilterMap(params));
    }

    private Map<String, Object> getActivityFilterMap(DefaultParams params) {
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("params", params);
        return map;
    }

    public Map getUserActivityById(Long userId, DefaultParams params)
            throws IdNotFoundException {
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("clusterId", params.getClusterId());
        map.put("intervalId", params.getIntervalId());
        map.put("userId", userId);
        Map user = (Map) this.getSqlMapClientTemplate().queryForObject(
                "user.activityById", map);
        if(user == null) {
            throw new IdNotFoundException("User not found with id: " + userId);
        }
        return user;
    }

    public Map getUserById(Long userId) throws IdNotFoundException {
        Map<String, Long> map = new HashMap<String, Long>();
        map.put("id", userId);
        Map user = (Map) this.getSqlMapClientTemplate().queryForObject(
                "user.fetch", map);
        if(user == null) {
            throw new IdNotFoundException("User not found with id: " + userId);
        }
        return user;
    }

    public Map getUserByName(String username) throws IdNotFoundException {
        Map<String, String> map = new HashMap<String, String>();
        map.put("name", username);
        Map user = (Map) this.getSqlMapClientTemplate().queryForObject(
                "user.fetch", map);
        if(user == null) {
            throw new IdNotFoundException("User not found with username: "
                    + username);
        }
        return user;
    }

    @SuppressWarnings("unchecked")
    public List<Map> getUserList() {
        return (List<Map>) this.getSqlMapClientTemplate().queryForList(
                "user.list");
    }
}
