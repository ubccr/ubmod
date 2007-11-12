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
 * PartialController.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.controller;

import java.io.IOException;
import java.util.Map;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.jfree.chart.servlet.ServletUtilities;
import org.springframework.web.servlet.ModelAndView;

import edu.buffalo.ccr.ubmod.dao.IdNotFoundException;
import edu.buffalo.ccr.ubmod.service.ChartService;
import edu.buffalo.ccr.ubmod.service.DataAccessFacade;
import edu.buffalo.ccr.ubmod.util.DefaultParams;
import edu.buffalo.ccr.ubmod.util.NumberHelper;

/**
 * Controller class which renders partial HTML. These are pages which get called
 * by Javascript on the client side and render templates which do not get
 * wrapped in the default template.
 */
public class PartialController extends BaseMultiActionController {
    private DataAccessFacade dataAccessFacade;
    private ChartService chartService;

    public ModelAndView dashboardPartial(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("dashboardPartial");
        DefaultParams params = DefaultParams.fromRequest(request);
        params.setSortBy("wallt");
        params.setSortDesc(true);
        mav.addObject("cluster", dataAccessFacade.getClusterDao()
                .getClusterActivity(params));

        try {
            mav.addObject("interval", dataAccessFacade.getLookupTableDao()
                    .getIntervalById(params.getIntervalId()));

            String userBarChart = ServletUtilities.saveChartAsPNG(chartService
                    .getUserUtilizationBarChart(params), 400, 350, request
                    .getSession(true));
            mav.addObject("userBarChart", userBarChart);

            String groupBarChart = ServletUtilities.saveChartAsPNG(chartService
                    .getGroupUtilizationBarChart(params), 400, 350, request
                    .getSession(true));
            mav.addObject("groupBarChart", groupBarChart);

            String userPieChart = ServletUtilities.saveChartAsPNG(chartService
                    .getUserUtilizationPieChart(params), 400, 350, request
                    .getSession(true));
            mav.addObject("userPieChart", userPieChart);

            String groupPieChart = ServletUtilities.saveChartAsPNG(chartService
                    .getGroupUtilizationPieChart(params), 400, 350, request
                    .getSession(true));
            mav.addObject("groupPieChart", groupPieChart);
        } catch(IOException e) {
            logger.fatal("I/O Error. Failed to render chart as PNG: "
                    + e.getMessage());
        } catch(IdNotFoundException e) {
            logger.fatal("Invalid id");
            return fatalErrorPartial("Failed to find a valid interval. Please make sure you have loaded data into the database using the shredder and have run the update command to build the aggregate tables.");
        }

        return mav;
    }

    public ModelAndView waitTimePartial(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("waitTimePartial");
        DefaultParams params = DefaultParams.fromRequest(request);
        try {
            String waitChart = ServletUtilities.saveChartAsPNG(chartService
                    .getWaitTimeChart(params), 700, 400, request
                    .getSession(true));
            mav.addObject("waitChart", waitChart);
        } catch(IOException e) {
            logger.fatal("I/O Error. Failed to render chart as PNG: "
                    + e.getMessage());
        }

        return mav;
    }

    public ModelAndView cpuConsumptionPartial(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("cpuConsumptionPartial");
        DefaultParams params = DefaultParams.fromRequest(request);

        try {
            String chart = ServletUtilities.saveChartAsPNG(chartService
                    .getCpuConsumptionChart(params), 700, 400, request
                    .getSession(true));
            mav.addObject("chart", chart);
        } catch(IOException e) {
            logger.fatal("I/O Error. Failed to render chart as PNG: "
                    + e.getMessage());
        }

        return mav;
    }

    public ModelAndView showGroupPartial(HttpServletRequest request,
            HttpServletResponse response) {

        DefaultParams params = DefaultParams.fromRequest(request);
        Long groupId = NumberHelper.createLong(request.getParameter("id"));

        ModelAndView mav = new ModelAndView("showGroupPartial");
        try {
            Map group = dataAccessFacade.getGroupDao().getGroupActivityById(
                    groupId, params);
            mav.addObject("group", group);
        } catch(IdNotFoundException e) {
            logger.warn("Failed to find group with id: " + groupId);
        }

        return mav;
    }

    public ModelAndView showUserPartial(HttpServletRequest request,
            HttpServletResponse response) {

        DefaultParams params = DefaultParams.fromRequest(request);
        Long userId = NumberHelper.createLong(request.getParameter("id"));
        ModelAndView mav = new ModelAndView("showUserPartial");
        try {
            Map user = dataAccessFacade.getUserDao().getUserActivityById(
                    userId, params);
            mav.addObject("user", user);
        } catch(IdNotFoundException e) {
            logger.warn("Failed to find user with id: " + userId);
        }

        return mav;
    }

    public ModelAndView showQueuePartial(HttpServletRequest request,
            HttpServletResponse response) {

        DefaultParams params = DefaultParams.fromRequest(request);
        Integer qid = NumberHelper.createInteger(request.getParameter("id"));
        ModelAndView mav = new ModelAndView("showQueuePartial");
        try {
            Map queue = dataAccessFacade.getQueueDao().getQueueActivityById(
                    qid, params);
            mav.addObject("queue", queue);
        } catch(IdNotFoundException e) {
            logger.warn("Failed to find queue with id: " + qid);
        }

        return mav;
    }

    public void setDataAccessFacade(DataAccessFacade dataAccessFacade) {
        this.dataAccessFacade = dataAccessFacade;
    }

    public void setChartService(ChartService chartService) {
        this.chartService = chartService;
    }
}
