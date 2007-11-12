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
 * DefaultParams.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.util;

import javax.servlet.http.HttpServletRequest;

/**
 * A convenience class to encapsulate request parameters from the web interface
 */
public class DefaultParams {
    private Integer limit;
    private Integer offset;
    private Integer clusterId;
    private Integer intervalId;
    private String sortBy;
    private Boolean sortDesc;
    private String filter;
    private String defaultSort;

    /**
     * Default column to sort by. Mostly used for the Javascript Grid.
     */
    public String getDefaultSort() {
        return defaultSort;
    }

    public void setDefaultSort(String defaultSort) {
        this.defaultSort = defaultSort;
    }

    /**
     * Default no arg constructor. Defaults to limit = 25, offest = 0 for paging
     * 
     */
    public DefaultParams() {
        this.limit = new Integer(25);
        this.offset = new Integer(0);
        this.sortDesc = new Boolean(false);
    }

    /**
     * Convenience method to create a new DefaultParams from an
     * HttpServletRequest. Sets default values if none are provided
     * 
     * @param request
     *            the http request
     * @param defaultSort
     *            default value to sort by
     */
    public static DefaultParams fromRequest(HttpServletRequest request,
            String defaultSort) {
        DefaultParams gp = DefaultParams.fromRequest(request);
        gp.setDefaultSort(defaultSort);
        return gp;
    }

    /**
     * Convenience method to create a new DefaultParams from an
     * HttpServletRequest. Sets default values if none are provided.
     * 
     * @param request
     *            the http request
     */
    public static DefaultParams fromRequest(HttpServletRequest request) {
        Integer offset = NumberHelper.createInteger(request
                .getParameter("start"), 0);
        Integer limit = NumberHelper.createInteger(request
                .getParameter("limit"), 25);

        // XXX remove hard coded database id's. should be configurable
        Integer clusterId = NumberHelper.createInteger(request
                .getParameter("cluster_id"), 1);
        Integer intervalId = NumberHelper.createInteger(request
                .getParameter("interval_id"), 3);
        String filter = request.getParameter("filter");
        String sortBy = request.getParameter("sort");
        String sortDir = request.getParameter("dir");

        DefaultParams gp = new DefaultParams();
        gp.setLimit(limit);
        gp.setOffset(offset);
        gp.setClusterId(clusterId);
        gp.setIntervalId(intervalId);
        if(filter != null && filter.length() > 0) {
            gp.setFilter(filter);
        }
        if(sortBy != null && sortBy.length() > 0) {
            gp.setSortBy(sortBy);
        }
        gp.setSortDescByString(sortDir);

        return gp;
    }

    /**
     * Column to filter results by. Used for the search functionality in the
     * Javascript grid
     */
    public String getFilter() {
        return filter;
    }

    public void setFilter(String filter) {
        this.filter = filter;
    }

    /**
     * Max limit of result set. Used for paging the Javascript grid
     */
    public Integer getLimit() {
        return limit;
    }

    public void setLimit(Integer limit) {
        this.limit = limit;
    }

    /**
     * Starting offset for the result set. Used of paging the Javascript grid
     */
    public Integer getOffset() {
        return offset;
    }

    public void setOffset(Integer offset) {
        this.offset = offset;
    }

    /**
     * Toggle sort direction
     */
    public Boolean getSortDesc() {
        return sortDesc;
    }

    public void setSortDesc(Boolean sortDesc) {
        this.sortDesc = sortDesc;
    }

    /**
     * List of valid columns to sort by.
     */
    private String[] getValidSortColumns() {
        // XXX hard coded list of columnns in the database. Need fix this.
        return new String[] { "group_name", "user", "jobs", "avg_wait",
                "wallt", "avg_cpus", "avg_mem", "disk_used" };
    }

    /**
     * Column in which to sort result set. Used in the Javascript grid
     * 
     * @return
     */
    public String getSortBy() {
        if(sortBy == null && defaultSort != null) {
            return defaultSort;
        } else {
            return sortBy;
        }
    }

    public void setSortBy(String sortBy) {
        String[] cols = this.getValidSortColumns();
        boolean ok = false;
        for(int i = 0; i < cols.length; i++) {
            if(cols[i].equals(sortBy)) {
                ok = true;
                break;
            }
        }

        if(!ok) {
            sortBy = this.getDefaultSort();
        }

        this.sortBy = sortBy;
    }

    /**
     * Returns the string value of the sort direction for SQL.
     */
    public void setSortDescByString(String sortDir) {
        // XXX this is a hack!! reverse the logic to have columns sort desc
        // first.
        if(sortDir == null || "ASC".equalsIgnoreCase(sortDir)) {
            this.setSortDesc(new Boolean(true));
        } else {
            this.setSortDesc(new Boolean(false));
        }
    }

    /**
     * ID of the cluster selected from a drop down list.
     */
    public Integer getClusterId() {
        return clusterId;
    }

    public void setClusterId(Integer clusterId) {
        this.clusterId = clusterId;
    }

    /**
     * ID of the interval selected from a drop down list
     */
    public Integer getIntervalId() {
        return intervalId;
    }

    public void setIntervalId(Integer intervalId) {
        this.intervalId = intervalId;
    }

    public String getSortDir() {
        if(this.getSortDesc().booleanValue()) {
            return "desc";
        } else {
            return "asc";
        }
    }
}
