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
 * Main.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.tools;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.commons.cli.OptionBuilder;
import org.apache.commons.cli.Options;
import org.apache.commons.cli.ParseException;
import org.apache.commons.cli.PosixParser;
import org.apache.commons.lang.time.DateUtils;
import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.springframework.context.ApplicationContext;
import org.springframework.context.support.ClassPathXmlApplicationContext;

/**
 * Main driver class for shredder/aggregate tool used in updating UBMoD
 * database.
 */
public class Main {
    private static Log logger = LogFactory.getLog(Main.class);
    private static final DateFormat dateFormat = new SimpleDateFormat(
            "yyyyMMdd");

    public static void main(String[] args) {
        Options options = new Options();
        options.addOption(OptionBuilder.withLongOpt("in").withDescription(
                "input file ('-' for stdin)").hasArg().create("i"));
        options.addOption(OptionBuilder.withLongOpt("help").withDescription(
                "print usage info").create("h"));
        options
                .addOption(OptionBuilder
                        .withLongOpt("host")
                        .withDescription(
                                "Explicity set host from which the log file(s) originated from")
                        .hasArg().create("H"));
        options.addOption(OptionBuilder.withLongOpt("verbose").withDescription(
                "verbose output").create("v"));
        options.addOption(OptionBuilder.withLongOpt("shred").withDescription(
                "shred pbs accounting file(s)").create("s"));
        options.addOption(OptionBuilder.withLongOpt("update").withDescription(
                "update aggregate tables").create("u"));
        options.addOption(OptionBuilder.withLongOpt("pbsdir").withDescription(
                "location of pbs accounting log directory. Auto-update")
                .hasArg().create("d"));

        CommandLineParser parser = new PosixParser();
        CommandLine cmd = null;
        try {
            cmd = parser.parse(options, args);
        } catch(ParseException e) {
            Main.printHelpAndExit(options, e.getMessage());
        }

        if(cmd.hasOption("h")) {
            Main.printHelpAndExit(options);
        }

        InputStream input = System.in;

        String in = cmd.getOptionValue("i");
        if(in != null && !"-".equals(in)) {
            try {
                input = new FileInputStream(in);
            } catch(FileNotFoundException e) {
                Main.printHelpAndExit(options, e.getMessage());
            }
        }

        File pbsDir = null;
        String pbsDirStr = cmd.getOptionValue("d");
        if(pbsDirStr != null) {
            pbsDir = new File(pbsDirStr);
            if(!pbsDir.exists() || !pbsDir.isDirectory()) {
                Main.printHelpAndExit(options,
                        "Failed to find pbs accounting directory at '"
                                + pbsDirStr + "'");
            }
        }

        ApplicationContext context = new ClassPathXmlApplicationContext(
                new String[] { "/edu/buffalo/ccr/ubmod/tools/beans.xml" });
        Shredder shredder = (Shredder) context.getBean("shredder");
        Aggregator aggregator = (Aggregator) context.getBean("aggregator");
        DataLoader dataLoader = (DataLoader) context.getBean("dataLoader");

        if(cmd.hasOption("H")) {
            String hostName = cmd.getOptionValue("H");
            shredder.setHost(hostName);
        }

        if(cmd.hasOption("s") && cmd.hasOption("d") && pbsDir != null) {
            Calendar today = Calendar.getInstance();
            String todayString = dateFormat.format(today.getTime());
            Date maxDate = dataLoader.getMaxDate(shredder.getHost());
            if(maxDate == null) {
                if(cmd.hasOption("v")) {
                    logger.fatal("Empty database. Shredding all files.");
                }
                String[] files = pbsDir.list();
                for(int i = 0; i < files.length; i++) {
                    if(todayString.equals(files[i])) {
                        continue;
                    }
                    if(cmd.hasOption("v")) {
                        logger.fatal("Processing file: " + files[i]);
                    }
                    File file = new File(pbsDir, files[i]);
                    try {
                        int jobs = shredder.shred(new FileInputStream(file));
                        if(cmd.hasOption("v")) {
                            logger.fatal("Successfully shredded " + jobs
                                    + " jobs.");
                        }
                    } catch(IOException e) {
                        logger
                                .fatal("I/O Error. Failed to shred pbs accounting file '"
                                        + file.getAbsolutePath()
                                        + "': "
                                        + e.getMessage());
                    }
                }
            } else {
                Calendar c = Calendar.getInstance();
                c.setTime(maxDate);
                c.add(Calendar.DATE, 1);

                String acctFileName = dateFormat.format(c.getTime());
                File file = new File(pbsDir, acctFileName);
                int count = 0;
                while(!DateUtils.isSameDay(c, today)) {
                    if(cmd.hasOption("v")) {
                        logger.fatal("Checking for file: " + file);
                    }
                    if(file.exists()) {
                        if(cmd.hasOption("v")) {
                            logger.fatal("Processing file: " + file);
                        }
                        try {
                            int jobs = shredder
                                    .shred(new FileInputStream(file));
                            if(cmd.hasOption("v")) {
                                logger.fatal("Successfully shredded " + jobs
                                        + " jobs.");
                            }
                            count++;
                        } catch(IOException e) {
                            logger
                                    .fatal("I/O Error. Failed to shred pbs accounting file '"
                                            + file.getAbsolutePath()
                                            + "': "
                                            + e.getMessage());
                        }
                    }
                    c.add(Calendar.DATE, 1);
                    acctFileName = dateFormat.format(c.getTime());
                    file = new File(pbsDir, acctFileName);
                }
                if(cmd.hasOption("v")) {
                    if(count == 0) {
                        logger.fatal("No new pbs accounting files found as of "
                                + dateFormat.format(maxDate));
                    } else {
                        logger.fatal("Shredded " + count + " files.");
                    }
                }
            }
        } else if(cmd.hasOption("s")) {
            try {
                if(cmd.hasOption("v")) {
                    if(input instanceof FileInputStream) {
                        logger.fatal("Processing file: "
                                + cmd.getOptionValue("i"));
                    } else {
                        logger.fatal("Processing stdin...");
                    }
                }
                int jobs = shredder.shred(input);
                if(cmd.hasOption("v")) {
                    logger.fatal("Successfully shredded " + jobs + " jobs.");
                }
            } catch(IOException e) {
                logger.fatal("I/O Error. Failed to shred pbs accounting file: "
                        + e.getMessage());
            }
        } else if(cmd.hasOption("u")) {
            if(cmd.hasOption("v")) {
                logger.fatal("Updating aggregate tables...");
            }
            try {
                aggregator.aggregate();
            } catch(AggregatorException e) {
                logger.fatal("Failed to run aggregator: " + e.getMessage());
            }
            if(cmd.hasOption("v")) {
                logger.fatal("Done.");
            }
        } else {
            Main.printHelpAndExit(options,
                    "Please specify a mode: --update OR --shred");
        }
    }

    public static void printHelpAndExit(Options options, String message) {
        if(message != null)
            logger.fatal("Usage error: " + message);
        HelpFormatter formatter = new HelpFormatter();
        formatter.printHelp("shredder", options);
        if(message != null) {
            System.exit(1);
        } else {
            System.exit(0);
        }
    }

    public static void printHelpAndExit(Options options) {
        printHelpAndExit(options, null);
    }

}
