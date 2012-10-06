package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Handler;
import android.os.Message;
import android.preference.PreferenceManager;
import at.rueckgr.android.ipwe.data.State;
import at.rueckgr.android.ipwe.data.Status;

public class CommonData {
	public static final int NOTIFICATION_ID = 1;
	
	public Intent pollServiceIntent;
	public PollService pollService;
	
	private static CommonData commonData;
	private Map<String, State> states;

	private Status status;

	private OverviewActivity context;

	private SharedPreferences preferences;

	private List<Handler> callbacks;

	private CommonData() {
		states = new HashMap<String, State>();
		
		states.put("ok", new State("ok", "#00cc33"));
		states.put("warning", new State("warning", "#00cc33"));
		states.put("critical", new State("critical", "#00cc33"));
		states.put("unknown", new State("unknown", "#00cc33"));
		
		callbacks = new ArrayList<Handler>();
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

	public void setContext(OverviewActivity context) {
		this.context = context;
	}

	public boolean isConfigured() {
		return getPreferences().getBoolean("configured", false);
	}

	// TODO don't hardcode default values here
	public String getSettingsURL() {
		return getPreferences().getString("settings_url", "");
	}
	
	// TODO getter for authenticator 
	public boolean getSettingsAuth() {
		return getPreferences().getBoolean("settings_auth", false);
	}

	public boolean getSettingsRefresh() {
		return getPreferences().getBoolean("settings_refresh", false);
	}
	
	public int getSettingsRefreshInterval() {
		// TOOD don't hardcode 300 here
		return getPreferences().getInt("settings_refresh_interval", 300);
	}
	
	private SharedPreferences getPreferences() {
		if(preferences == null) {
			if(context != null) {
				preferences = PreferenceManager.getDefaultSharedPreferences(context);
			}
			else {
				// TODO epic problem
				return null;
			}
		}
		return preferences;
	}
	
	public void notifyUpdate(Status status) {
		for(Handler callback : callbacks) {
			// TODO magic number 0?
			Message message = Message.obtain(callback, 0, status);
			callback.sendMessage(message);
		}
	}
	
	public void addCallback(Handler callback) {
		callbacks.add(callback);
	}
}
