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
 * PBSShredder.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.tools;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.text.DateFormat;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.springframework.context.ApplicationContext;
import org.springframework.context.support.ClassPathXmlApplicationContext;
import org.springframework.orm.ibatis.support.SqlMapClientDaoSupport;

/**
 * Shredder implementation for PBS/TORQUE accounting log files
 */
public class PBSShredder extends SqlMapClientDaoSupport implements Shredder {
    private static Log logger = LogFactory.getLog(PBSShredder.class);
    private static Pattern memoryPattern = Pattern.compile("^\\d+(.*)");
    private static final DateFormat dateFormat = new SimpleDateFormat(
            "MM/dd/yyyy HH:mm:ss");

    private DataLoader dataLoader;
    private String host;

    public static void main(String[] args) throws Exception {
        ApplicationContext context = new ClassPathXmlApplicationContext(
                new String[] { "beans.xml" });
        PBSShredder shred = (PBSShredder) context.getBean("shredder");

        File dir = new File("/home/aebruno2/projects/pbsusage/newtest/");
        String[] files = dir.list();
        for(int i = 0; i < files.length; i++) {
            logger.info("Processing file: " + files[i]);
            int x = shred.shred(new FileInputStream(new File(dir, files[i])));
            logger.info("Shredded " + x + " records.");
            // break;
        }
        logger.info("Done!");
    }

    public int shred(InputStream input) throws IOException {
        int count = 0;
        BufferedReader reader = new BufferedReader(new InputStreamReader(input));
        String line = null;
        while((line = reader.readLine()) != null) {
            String params = null;
            String[] fields = line.split(";");
            if(fields.length == 4) {
                params = fields[3];
            } else if(fields.length != 3) {
                logger.fatal("Malformed pbs acct line: " + line);
                continue;
            }

            DefaultEventRecord rec = new DefaultEventRecord();
            rec.setEventType(EventType.fromString(fields[1]));
            try {
                rec.setDateKey(dateFormat.parse(fields[0]));
            } catch(ParseException e) {
                logger
                        .fatal("Failed to parse date from line in pbs acct file: "
                                + fields[0]);
                continue;
            }
            try {
                this.setJobIdAndHost(rec, fields[2]);
                if(params != null) {
                    String[] parts = params.split("\\s+");
                    for(int i = 0; i < parts.length; i++) {
                        int index = parts[i].indexOf('=');
                        if(index == -1) {
                            logger
                                    .fatal("Malformed param in pbs acct line. no '=' found: "
                                            + parts[i]);
                            continue;
                        }
                        String key = parts[i].substring(0, index);
                        String val = parts[i].substring(index + 1);
                        key = key.replaceAll("\\.", "_");
                        key = key.toLowerCase();

                        if("resources_used_vmem".equals(key)) {
                            rec.setResourcesUsedVmem(this.parseMemory(val));
                        } else if("resources_used_mem".equals(key)) {
                            rec.setResourcesUsedMem(this.parseMemory(val));
                        } else if("resource_list_mem".equals(key)) {
                            rec.setResourceListMem(this.parseMemory(val));
                        } else if("resource_list_pmem".equals(key)) {
                            rec.setResourceListPmem(this.parseMemory(val));
                        } else if("resources_used_walltime".equals(key)) {
                            rec.setResourcesUsedWalltime(this.parseTime(val));
                        } else if("resources_used_cput".equals(key)) {
                            rec.setResourcesUsedCput(this.parseTime(val));
                        } else if("resource_list_pcput".equals(key)) {
                            rec.setResourceListPcput(this.parseTime(val));
                        } else if("resource_list_cput".equals(key)) {
                            rec.setResourceListCput(this.parseTime(val));
                        } else if("resource_list_walltime".equals(key)) {
                            rec.setResourceListWalltime(this.parseTime(val));
                        } else if("exec_host".equals(key)) {
                            this.setExecHost(rec, val);
                        } else if("ctime".equals(key)) {
                            rec.setCreationTime(this.parseUnixDate(val));
                        } else if("qtime".equals(key)) {
                            rec.setQueueTime(this.parseUnixDate(val));
                        } else if("start".equals(key)) {
                            rec.setStartTime(this.parseUnixDate(val));
                        } else if("end".equals(key)) {
                            rec.setEndTime(this.parseUnixDate(val));
                        } else if("etime".equals(key)) {
                            rec.setEligibleTime(this.parseUnixDate(val));
                        } else if("user".equals(key)) {
                            rec.setUser(val);
                        } else if("group".equals(key)) {
                            rec.setGroup(val);
                        } else if("queue".equals(key)) {
                            rec.setQueue(val);
                        } else if("exit_status".equals(key)) {
                            rec.setExitStatus(val);
                        } else if("requestor".equals(key)) {
                            rec.setRequestor(val);
                        } else if("session".equals(key)) {
                            rec.setSession(val);
                        } else if("jobname".equals(key)) {
                            rec.setJobname(val);
                        } else if("account".equals(key)) {
                            rec.setAccount(val);
                        } else if("resource_list_nodes".equals(key)) {
                            rec.setResourceListNodes(val);
                        } else if("resource_list_procs".equals(key)) {
                            rec.setResourceListProcs(val);
                        } else if("resource_list_neednodes".equals(key)) {
                            rec.setResourceListNeednodes(val);
                        } else if("resource_list_ncpus".equals(key)) {
                            rec.setResourceListNcpus(val);
                        } else if("resource_list_nodect".equals(key)) {
                            rec.setResourceListNodect(val);
                        }
                    }
                }
            } catch(InvalidDataException e) {
                logger.fatal("Malformed pbs acct line: " + e.getMessage());
                continue;
            }

            Long eventId = null;
            try {
                eventId = this.dataLoader.load(rec);
            } catch(LoadingException e) {
                logger.fatal("Failed to load event record: " + e.getMessage());
                continue;
            }
            count++;

            // XXX experimenting with catpuring data at the host level. This way
            // we'd know which jobs ran on which host. Consider breaking this
            // out to the DataLoader interface.
            if(rec.getExecHost() != null) {
                try {
                    List<Map<String, Object>> hosts = this.parseHosts(rec
                            .getExecHost());
                    for(Map<String, Object> host : hosts) {
                        host.put("eventId", eventId);
                        this.getSqlMapClientTemplate().insert(
                                "event.insertHostLog", host);
                    }
                } catch(InvalidDataException e) {
                    logger
                            .fatal("Failed to log host activity. Malformed exec_host field: "
                                    + e.getMessage());
                }
            }
        }
        logger.info("Total shredded: " + count);
        return count;
    }

    private void setJobIdAndHost(DefaultEventRecord rec, String val)
            throws InvalidDataException {
        int index = val.indexOf('.');
        Long id = null;
        try {
            id = Long.valueOf(val.substring(0, index));
        } catch(NumberFormatException e) {
            throw new InvalidDataException("Invalid job id: "
                    + val.substring(0, index), e);
        }
        String host = val.substring(index + 1);
        rec.setJobId(id);
        if(this.host == null) {
            rec.setHost(host);
        } else {
            rec.setHost(this.host);
        }
    }

    private Long parseMemory(String val) throws InvalidDataException {
        Matcher m = memoryPattern.matcher(val);
        String unit = "kb";
        if(m.matches()) {
            unit = m.group(1);
        }
        Long mem = null;
        val = val.replaceAll("\\D+", "");
        try {
            mem = Long.valueOf(val);
        } catch(NumberFormatException e) {
            throw new InvalidDataException("Invalid mem value: " + val, e);
        }
        mem = this.scaleMemory(unit, mem);
        return mem;
    }

    private Long parseUnixDate(String val) throws InvalidDataException {
        Long unixTime = null;
        try {
            unixTime = Long.valueOf(val);
        } catch(NumberFormatException e) {
            throw new InvalidDataException("Invalid unix timestamp: " + val, e);
        }

        return unixTime;
    }

    private Long parseTime(String val) throws InvalidDataException {
        String[] parts = val.split(":");
        if(parts.length != 3) {
            throw new InvalidDataException("Invalid time value: " + val);
        }

        Integer h = null;
        Integer m = null;
        Integer s = null;

        try {
            h = Integer.valueOf(parts[0]);
            m = Integer.valueOf(parts[1]);
            s = Integer.valueOf(parts[2]);

            if(h < 0) h = new Integer(0);
            if(m < 0) m = new Integer(0);
            if(s < 0) s = new Integer(0);
        } catch(NumberFormatException e) {
            throw new InvalidDataException("Invalid time value: " + val, e);
        }

        long seconds = s.longValue();
        seconds += h.intValue() * 60 * 60;
        seconds += m.intValue() * 60;
        return new Long(seconds);
    }

    private void setExecHost(DefaultEventRecord rec, String val)
            throws InvalidDataException {
        List<Map<String, Object>> hosts = this.parseHosts(val);
        Map<String, Integer> map = new HashMap<String, Integer>();
        for(Map h : hosts) {
            String host = (String) h.get("host");
            Integer totalCpus = (Integer) map.get(host);
            if(totalCpus == null) {
                totalCpus = new Integer(0);
            }

            totalCpus = new Integer(totalCpus.intValue() + 1);
            map.put(host, totalCpus);
        }
        int cpus = 0;
        int nodes = 0;
        for(Iterator e = map.keySet().iterator(); e.hasNext();) {
            String k = (String) e.next();
            Integer v = (Integer) map.get(k);
            nodes++;
            cpus += v.intValue();
        }

        rec.setResourcesUsedNodes(new Integer(nodes));
        rec.setResourcesUsedCpus(new Integer(cpus));
        rec.setExecHost(val);
    }

    private List<Map<String, Object>> parseHosts(String execHost)
            throws InvalidDataException {
        List<Map<String, Object>> hosts = new ArrayList<Map<String, Object>>();
        String[] parts = execHost.split("\\+");
        for(int i = 0; i < parts.length; i++) {
            String[] fields = parts[i].split("\\/");
            if(fields.length == 2) {
                Map<String, Object> h = new HashMap<String, Object>();
                Integer cpu = null;
                try {
                    cpu = Integer.valueOf(fields[1]);
                } catch(NumberFormatException e) {
                    throw new InvalidDataException(
                            "Invalid CPU number for exec_host: " + execHost);
                }
                h.put("host", fields[0]);
                h.put("cpu", cpu);
                hosts.add(h);
            }
        }
        return hosts;
    }

    private Long scaleMemory(String unit, Long value) {
        Long scaled = new Long(value.longValue());
        if("mb".equals(unit)) {
            scaled = new Long(value.longValue() * 1024);
        } else if("gb".equals(unit)) {
            scaled = new Long((value.longValue() * 1024) * 1024);
        } else if("b".equals(unit)) {
            // XXX because our default unit is KB just return 1
            scaled = new Long(1);
        }

        return scaled;
    }

    public String getHost() {
        return host;
    }

    public void setHost(String host) {
        this.host = host;
    }

    public void setDataLoader(DataLoader dataLoader) {
        this.dataLoader = dataLoader;
    }

}
