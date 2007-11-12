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
 * ClusterDaoIbatisImpl.java
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
 * iBATIS Implementation for the Cluster Data Access Object Interface
 */
public class ClusterDaoIbatisImpl extends SqlMapClientDaoSupport implements
        ClusterDao {

    @SuppressWarnings("unchecked")
    public List<Map> getClusterList() {
        Map<String,Object> map = new HashMap<String,Object>();
        //XXX filter cluster list by username here
        return (List<Map>) this.getSqlMapClientTemplate().queryForList(
                "cluster.list", map);
    }
    
    public Map getClusterActivity(DefaultParams params) {
        return (Map) this.getSqlMapClientTemplate().queryForObject(
                "cluster.activity", params);
    }

    public Map getClusterById(Integer clusterId) throws IdNotFoundException {
        Map<String, Integer> map = new HashMap<String, Integer>();
        map.put("id", clusterId);
        Map c = (Map) this.getSqlMapClientTemplate().queryForObject(
                "cluster.fetch", map);
        if(c == null) {
            throw new IdNotFoundException("Cluster not found with id: "
                    + clusterId);
        }
        return c;
    }
}