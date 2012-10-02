package at.rueckgr.android.ipwe;

import java.util.HashMap;
import java.util.Map;

import android.content.Intent;
import at.rueckgr.android.ipwe.data.State;
import at.rueckgr.android.ipwe.data.Status;

public class CommonData {
	public static final int NOTIFICATION_ID = 1;
	
	public Intent pollServiceIntent;
	public PollService pollService;
	
	private static CommonData commonData;
	private Map<String, State> states;

	private Status status;
	
	private CommonData() {
		states = new HashMap<String, State>();
		
		states.put("ok", new State("ok", "#00cc33"));
		states.put("warning", new State("warning", "#00cc33"));
		states.put("critical", new State("critical", "#00cc33"));
		states.put("unknown", new State("unknown", "#00cc33"));
	}

	public static CommonData getInstance() {
		if(commonData == null) {
			commonData = new CommonData();
		}
		return commonData;
	}
	
	public State getState(String name) {
		return states.get(name);
	}

	public Status getStatus() {
		return status;
	}

	public void setStatus(Status status) {
		this.status = status;
	}
}
