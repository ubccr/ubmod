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
 * JSONController.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.controller;

import java.io.IOException;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import net.sf.json.JSONObject;

import org.springframework.web.servlet.ModelAndView;
import org.springframework.web.servlet.mvc.multiaction.MultiActionController;

import edu.buffalo.ccr.ubmod.service.DataAccessFacade;
import edu.buffalo.ccr.ubmod.util.DefaultParams;

/**
 * Controller class for serving JSON data. This class provides methods for
 * fetching data and returning it in JSON format for use with Javascript on the
 * client side.
 */
public class JSONController extends MultiActionController {
    private DataAccessFacade dataAccessFacade;

    public ModelAndView userActivity(HttpServletRequest request,
            HttpServletResponse response) {
        DefaultParams params = DefaultParams.fromRequest(request, "wallt");

        List<Map> activity = dataAccessFacade.getUserDao().getAllUserActivity(
                params);
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("total", dataAccessFacade.getUserDao().getAllUserActivityCount(
                params));
        map.put("users", activity);
        return this.writeJSON(response, map);
    }

    public ModelAndView groupActivity(HttpServletRequest request,
            HttpServletResponse response) {
        DefaultParams params = DefaultParams.fromRequest(request, "wallt");

        List<Map> activity = dataAccessFacade.getGroupDao()
                .getAllGroupActivity(params);
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("total", dataAccessFacade.getGroupDao()
                .getAllGroupActivityCount(params));
        map.put("groups", activity);
        return this.writeJSON(response, map);

    }

    public ModelAndView queueActivity(HttpServletRequest request,
            HttpServletResponse response) {
        DefaultParams params = DefaultParams.fromRequest(request, "wallt");

        List activity = dataAccessFacade.getQueueDao().getAllQueueActivity(
                params);
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("total", dataAccessFacade.getQueueDao()
                .getAllQueueActivityCount(params));
        map.put("queue", activity);
        return this.writeJSON(response, map);
    }

    public ModelAndView clusterList(HttpServletRequest request,
            HttpServletResponse response) {
        List<Map> list = dataAccessFacade.getClusterDao().getClusterList();
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("total", new Integer(list.size()));
        map.put("data", list);

        return this.writeJSON(response, map);
    }

    public ModelAndView intervalList(HttpServletRequest request,
            HttpServletResponse response) {

        List<Map> list = dataAccessFacade.getLookupTableDao().getIntervalList();
        Map<String, Object> map = new HashMap<String, Object>();
        map.put("total", new Integer(list.size()));
        map.put("data", list);

        return this.writeJSON(response, map);
    }

    private ModelAndView writeJSON(HttpServletResponse response, Map map) {
        JSONObject json = JSONObject.fromObject(map);
        response.setContentType("text/plain");
        try {
            response.getWriter().print(json);
        } catch(IOException e) {
            logger.fatal("I/O Error. Failed to write out JSON object: "
                    + e.getMessage());
        }
        return null;
    }

    public void setDataAccessFacade(DataAccessFacade dataAccessFacade) {
        this.dataAccessFacade = dataAccessFacade;
    }
}
