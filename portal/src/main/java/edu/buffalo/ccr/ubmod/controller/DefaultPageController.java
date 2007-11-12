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
 * DefaultPageController.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.controller;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.springframework.web.servlet.ModelAndView;

/**
 * Controller class for the default pages of the UBMoD application.
 */
public class DefaultPageController extends BaseMultiActionController {

    public ModelAndView about(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/about");
        mav.addObject("page", "about");
        return mav;
    }

    public ModelAndView dashboard(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/dashboard");
        mav.addObject("page", "dash");
        return mav;
    }

    public ModelAndView cpuConsumption(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/cpu_consumption");
        mav.addObject("page", "cpu");
        return mav;
    }

    public ModelAndView waitTime(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/wait_time");
        mav.addObject("page", "wait");
        return mav;
    }

    public ModelAndView groupActivity(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/group/activity");
        mav.addObject("page", "group");
        return mav;
    }

    public ModelAndView queueActivity(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/queue/activity");
        mav.addObject("page", "queue");
        return mav;
    }

    public ModelAndView userActivity(HttpServletRequest request,
            HttpServletResponse response) {
        ModelAndView mav = new ModelAndView("/views/user/activity");
        mav.addObject("page", "user");
        return mav;
    }
}
