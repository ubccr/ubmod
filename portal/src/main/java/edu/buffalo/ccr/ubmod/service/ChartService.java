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
 * ChartService.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.service;

import java.awt.Font;
import java.math.BigDecimal;
import java.text.DateFormat;
import java.text.DecimalFormat;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Map;

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.jfree.chart.ChartColor;
import org.jfree.chart.ChartFactory;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.axis.CategoryAxis;
import org.jfree.chart.axis.CategoryLabelPositions;
import org.jfree.chart.axis.NumberAxis;
import org.jfree.chart.labels.StandardCategoryItemLabelGenerator;
import org.jfree.chart.labels.StandardPieSectionLabelGenerator;
import org.jfree.chart.plot.CategoryPlot;
import org.jfree.chart.plot.PiePlot;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.chart.renderer.category.CategoryItemRenderer;
import org.jfree.chart.title.TextTitle;
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;

import edu.buffalo.ccr.ubmod.dao.IdNotFoundException;
import edu.buffalo.ccr.ubmod.util.DefaultParams;

/**
 * Chart Service class provides methods for generating various charts.
 */
public class ChartService {
    private static Log logger = LogFactory.getLog(ChartService.class);
    private static DateFormat dateFormat = new SimpleDateFormat("MM/dd/yyyy");
    private DataAccessFacade dataAccessFacade;

    public JFreeChart getCpuConsumptionChart(DefaultParams params) {
        DefaultCategoryDataset dataSet = new DefaultCategoryDataset();

        List<Map> list = dataAccessFacade.getChartDao().getCpuConsumption(
                params);
        for(Map rec : list) {
            dataSet.addValue((Number) rec.get("cput"), "cpu", (String) rec
                    .get("label"));
        }

        JFreeChart chart = ChartFactory.createBarChart(null,
                "Number of CPUs/Job", "Delivered CPU time [cpu days]", dataSet,
                PlotOrientation.VERTICAL, false, false, false);

        CategoryPlot plot = (CategoryPlot) chart.getPlot();
        plot.setNoDataMessage("No job data found.");
        NumberAxis range = (NumberAxis) plot.getRangeAxis();
        range.setNumberFormatOverride(new DecimalFormat("#,###,###"));
        CategoryAxis x = (CategoryAxis) plot.getDomainAxis();
        x.setCategoryLabelPositions(CategoryLabelPositions.UP_45);
        CategoryItemRenderer r = plot.getRenderer();
        r.setItemLabelsVisible(true);
        r.setItemLabelGenerator(new StandardCategoryItemLabelGenerator(
                StandardCategoryItemLabelGenerator.DEFAULT_LABEL_FORMAT_STRING,
                new DecimalFormat("#,###")));

        chart.setBackgroundPaint(ChartColor.white);
        chart.setTitle(new TextTitle("CPU Consumption vs. Job Size", new Font(
                "SansSerif", Font.BOLD, 12)));
        addSubtitle(chart, params);
        return chart;
    }

    private void addSubtitle(JFreeChart chart, DefaultParams params) {
        try {
            Map interval = dataAccessFacade.getLookupTableDao()
                    .getIntervalById(params.getIntervalId());
            Map cluster = dataAccessFacade.getClusterDao().getClusterById(
                    params.getClusterId());
            Date start = (Date) interval.get("start");
            Date end = (Date) interval.get("end");
            String displayName = (String) cluster.get("display_name");
            if(displayName == null || displayName.length() == 0) {
                displayName = (String) cluster.get("host");
            }

            String title = "";
            if(!"All".equalsIgnoreCase(displayName)) {
                title += "Cluster: " + displayName;
            }
            title += " From: " + dateFormat.format(start);
            title += " To: " + dateFormat.format(end);
            TextTitle t1 = new TextTitle(title, new Font("SansSerif",
                    Font.PLAIN, 10));
            chart.addSubtitle(t1);
        } catch(IdNotFoundException e) {
            logger.fatal("Interval not found! Failed to create chart subtitle");
        }
    }

    public JFreeChart getUserUtilizationPieChart(DefaultParams params) {
        DefaultPieDataset dataSet = new DefaultPieDataset();

        // grab all the users
        params.setOffset(null);
        List<Map> list = dataAccessFacade.getUserDao().getAllUserActivity(
                params);
        int count = 0;
        int max = 11;
        BigDecimal other = new BigDecimal(0);
        for(Map rec : list) {
            if(count < max) {
                dataSet.setValue((String) rec.get("user"), (Number) rec
                        .get("wallt"));
            } else {
                BigDecimal num = (BigDecimal) rec.get("wallt");
                other = other.add(num);
            }
            count++;
        }
        if(count >= max) {
            dataSet.setValue("Remaining Users", other);
        }

        JFreeChart chart = ChartFactory.createPieChart(null, dataSet, true,
                false, false);

        PiePlot plot = (PiePlot) chart.getPlot();
        plot.setNoDataMessage("No user data found");
        plot
                .setLabelGenerator(new StandardPieSectionLabelGenerator(
                        "{0} ({2})"));

        chart.setBackgroundPaint(ChartColor.white);
        chart.setTitle(new TextTitle("User Utilization", new Font("SansSerif",
                Font.BOLD, 12)));

        return chart;
    }

    public JFreeChart getUserUtilizationBarChart(DefaultParams params) {
        DefaultCategoryDataset dataSet = new DefaultCategoryDataset();

        // grab all the users
        params.setOffset(null);
        List<Map> list = dataAccessFacade.getUserDao().getAllUserActivity(
                params);
        int count = 0;
        for(Map rec : list) {
            count++;
            dataSet.addValue((Number) rec.get("wallt"), "wallt", (String) rec
                    .get("user"));
            if(count > 20)
                break;
        }

        JFreeChart chart = ChartFactory.createBarChart(null, null,
                "Wall time days", dataSet, PlotOrientation.VERTICAL, false,
                false, false);

        CategoryPlot plot = (CategoryPlot) chart.getPlot();
        plot.setNoDataMessage("No user data found");
        NumberAxis range = (NumberAxis) plot.getRangeAxis();
        range.setNumberFormatOverride(new DecimalFormat("#,###,###"));
        CategoryAxis x = (CategoryAxis) plot.getDomainAxis();
        x.setCategoryLabelPositions(CategoryLabelPositions.UP_45);
        chart.setBackgroundPaint(ChartColor.white);
        chart.setTitle(new TextTitle("User Utilization", new Font("SansSerif",
                Font.BOLD, 12)));
        return chart;
    }

    public JFreeChart getGroupUtilizationPieChart(DefaultParams params) {
        DefaultPieDataset dataSet = new DefaultPieDataset();

        params.setOffset(null);
        List<Map> list = dataAccessFacade.getGroupDao().getAllGroupActivity(
                params);
        int count = 0;
        int max = 11;
        BigDecimal other = new BigDecimal(0);
        for(Map rec : list) {
            if(count < max) {
                dataSet.setValue((String) rec.get("group_name"), (Number) rec
                        .get("wallt"));
            } else {
                BigDecimal num = (BigDecimal) rec.get("wallt");
                other = other.add(num);
            }
            count++;
        }
        if(count >= max) {
            dataSet.setValue("Remaining Groups", other);
        }

        JFreeChart chart = ChartFactory.createPieChart(null, dataSet, true,
                false, false);

        PiePlot plot = (PiePlot) chart.getPlot();
        plot.setNoDataMessage("No group data found");
        plot
                .setLabelGenerator(new StandardPieSectionLabelGenerator(
                        "{0} ({2})"));
        chart.setBackgroundPaint(ChartColor.white);
        chart.setTitle(new TextTitle("Group Utilization", new Font("SansSerif",
                Font.BOLD, 12)));
        return chart;
    }

    public JFreeChart getGroupUtilizationBarChart(DefaultParams params) {
        DefaultCategoryDataset dataSet = new DefaultCategoryDataset();

        params.setOffset(null);
        List<Map> list = dataAccessFacade.getGroupDao().getAllGroupActivity(
                params);
        int count = 0;
        for(Map rec : list) {
            count++;
            dataSet.addValue((Number) rec.get("wallt"), "wallt", (String) rec
                    .get("group_name"));
            if(count > 20)
                break;
        }

        JFreeChart chart = ChartFactory.createBarChart(null, null,
                "Wall time days", dataSet, PlotOrientation.VERTICAL, false,
                false, false);

        CategoryPlot plot = (CategoryPlot) chart.getPlot();
        plot.setNoDataMessage("No group data found");
        NumberAxis range = (NumberAxis) plot.getRangeAxis();
        range.setNumberFormatOverride(new DecimalFormat("#,###,###"));
        CategoryAxis x = (CategoryAxis) plot.getDomainAxis();
        x.setCategoryLabelPositions(CategoryLabelPositions.UP_45);
        chart.setBackgroundPaint(ChartColor.white);
        chart.setTitle(new TextTitle("Group Utilization", new Font("SansSerif",
                Font.BOLD, 12)));
        return chart;
    }

    public JFreeChart getWaitTimeChart(DefaultParams params) {
        DefaultCategoryDataset dataSet = new DefaultCategoryDataset();

        List<Map> list = dataAccessFacade.getChartDao().getWaitTime(params);
        for(Map rec : list) {
            Number value = (Number) rec.get("avg_wait");
            if(value == null) {
                value = new Integer(0);
            }
            dataSet.addValue(value, "wait", (String) rec.get("label"));
        }

        JFreeChart chart = ChartFactory.createBarChart(null,
                "Number of CPUs/Job", "Avg. Wait time hours", dataSet,
                PlotOrientation.VERTICAL, false, false, false);

        CategoryPlot plot = (CategoryPlot) chart.getPlot();
        plot.setNoDataMessage("No job data found.");
        NumberAxis range = (NumberAxis) plot.getRangeAxis();
        range.setNumberFormatOverride(new DecimalFormat("#,###,###"));

        CategoryAxis x = (CategoryAxis) plot.getDomainAxis();
        x.setCategoryLabelPositions(CategoryLabelPositions.UP_45);
        CategoryItemRenderer r = plot.getRenderer();
        r.setItemLabelsVisible(true);
        r.setItemLabelGenerator(new StandardCategoryItemLabelGenerator(
                StandardCategoryItemLabelGenerator.DEFAULT_LABEL_FORMAT_STRING,
                new DecimalFormat("#,###")));

        chart.setBackgroundPaint(ChartColor.white);
        chart.setTitle(new TextTitle("Job Wait vs. Job Size", new Font(
                "SansSerif", Font.BOLD, 12)));
        addSubtitle(chart, params);
        return chart;
    }

    public void setDataAccessFacade(DataAccessFacade dataAccessFacade) {
        this.dataAccessFacade = dataAccessFacade;
    }
}
