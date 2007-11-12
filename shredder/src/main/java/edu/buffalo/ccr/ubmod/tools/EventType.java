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
 * EventType.java
 * ------------------------------------
 * Original Author: Andrew E. Bruno (CCR);
 * Contributor(s): -;
 * 
 */

package edu.buffalo.ccr.ubmod.tools;

/**
 * The EventType enum represents the different types of event records that can
 * be found in a log file. These are taken directly from PBS accounting log
 * files.
 */
public enum EventType {
    /**
     * Job was aborted by server
     */
    ABORT {
        public String toString() {
            return "A";
        }
    },

    /**
     * Beginning of reservation peroid
     */
    BEGIN {
        public String toString() {
            return "B";
        }
    },

    /**
     * Job was checkpointed and held
     */
    CHECKPOINT {
        public String toString() {
            return "C";
        }
    },

    /**
     * Job was deleted by request
     */
    DELETE {
        public String toString() {
            return "D";
        }
    },

    /**
     * Job ended (terminated execution)
     */
    END {
        public String toString() {
            return "E";
        }
    },

    /**
     * Resources reservation perioid finished
     */
    FINISH {
        public String toString() {
            return "F";
        }
    },

    /**
     * Scheduler or server requested removal of reservation
     */
    REMOVE {
        public String toString() {
            return "K";
        }
    },

    /**
     * Resources reservation termiated by ordinary client
     */
    TERMINATE {
        public String toString() {
            return "k";
        }
    },

    /**
     * Job was entered into a queue
     */
    QUEUE {
        public String toString() {
            return "Q";
        }
    },

    /**
     * Job was rerun
     */
    RERUN {
        public String toString() {
            return "R";
        }
    },

    /**
     * Job execution started
     */
    START {
        public String toString() {
            return "S";
        }
    },

    /**
     * Job was restarted from a checkpoint file
     */
    RESTART {
        public String toString() {
            return "T";
        }
    },

    /**
     * Created unconfirmed resources reservation on server
     */
    UNCONFIRMED {
        public String toString() {
            return "U";
        }
    },

    /**
     * Resources reservation confirmed by scheduler
     */
    CONFIRMED {
        public String toString() {
            return "Y";
        }
    };

    public static EventType fromString(String type) {
        for(EventType e : EventType.values()) {
            if(e.toString().equals(type)) {
                return e;
            }
        }

        return null;
    }
}
