package at.rueckgr.android.ipwe;

import java.io.IOException;
import java.io.InputStream;
import java.net.URI;
import java.net.URISyntaxException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.http.HttpException;
import org.apache.http.HttpRequest;
import org.apache.http.HttpRequestInterceptor;
import org.apache.http.HttpResponse;
import org.apache.http.auth.AuthState;
import org.apache.http.auth.Credentials;
import org.apache.http.auth.UsernamePasswordCredentials;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.protocol.ClientContext;
import org.apache.http.impl.auth.BasicScheme;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.protocol.HttpContext;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Handler;
import android.os.Message;
import android.preference.PreferenceManager;
import at.rueckgr.android.ipwe.data.Measurement;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.State;
import at.rueckgr.android.ipwe.data.Status;

// TODO rename to Common?
public class CommonData {
	public static final int NOTIFICATION_ID = 1;

	public static final int MESSAGE_UPDATE_SUCCESS = 0;

	public static final int MESSAGE_UPDATE_ERROR = 1;
	
	public Intent pollServiceIntent;
	public PollService pollService;
	
	private static CommonData commonData;
	private Map<String, State> states;

	private Status status;

	private OverviewActivity context;

	private SharedPreferences preferences;

	private List<Handler> callbacks;

	private boolean configured;

	private String settingsURL;

	private boolean settingsRefresh;

	private int settingsRefreshInterval;

	private String settingsUsername;

	private String settingsPassword;

	private boolean settingsAuth;

	private CommonData() {
		states = new HashMap<String, State>();
		
		states.put("ok", new State("ok", "#00cc33", true));
		states.put("warning", new State("warning", "#00cc33", false));
		states.put("critical", new State("critical", "#00cc33", false));
		states.put("unknown", new State("unknown", "#00cc33", false));
		
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

	public void setContext(OverviewActivity context) throws SensorsException {
		this.context = context;
		
		readConfig();
	}

	public void readConfig() throws SensorsException {
		configured = getPreferences().getBoolean("configured", false);
		settingsURL = getPreferences().getString("settings_url", "");
		settingsRefresh = getPreferences().getBoolean("settings_refresh", false);
		try {
			settingsRefreshInterval = Integer.parseInt(getPreferences().getString("settings_refresh_interval", "300"));
		}
		catch (NumberFormatException e) {
			throw new SensorsException(e);
		}
		settingsUsername = getPreferences().getString("settings_username", "");
		settingsPassword = getPreferences().getString("settings_password", "");
		settingsAuth = getPreferences().getBoolean("settings_auth", false);
	}

	public boolean isConfigured() {
		return configured;
	}

	public String getSettingsURL() {
		return settingsURL;
	}
	
	public boolean getSettingsRefresh() {
		return settingsRefresh;
	}
	
	public int getSettingsRefreshInterval() {
		return settingsRefreshInterval;
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
			Message message = Message.obtain(callback, CommonData.MESSAGE_UPDATE_SUCCESS, status);
			callback.sendMessage(message);
		}
	}
	
	public void addCallback(Handler callback) {
		callbacks.add(callback);
	}

	public InputStream executeHttpGet(String url) throws SensorsException {
		final URI uri;
		try {
			uri = new URI(url);
		} catch (URISyntaxException e) {
			throw new SensorsException(e);
		}
		DefaultHttpClient httpClient = new DefaultHttpClient();
		
		if(settingsAuth) {
			HttpRequestInterceptor preemptiveAuth = new HttpRequestInterceptor() {
			    public void process(final HttpRequest request, final HttpContext context) throws HttpException, IOException {
			        AuthState authState = (AuthState) context.getAttribute(ClientContext.TARGET_AUTH_STATE);
		            Credentials credentials =
		            		new UsernamePasswordCredentials(settingsUsername, settingsPassword);
	                authState.setAuthScheme(new BasicScheme());
	                authState.setCredentials(credentials);
			    }    
			};
			httpClient.addRequestInterceptor(preemptiveAuth, 0);
		}
		try {
			HttpResponse httpResponse = httpClient.execute(new HttpGet(uri));
			return httpResponse.getEntity().getContent();
		} catch (ClientProtocolException e) {
			throw new SensorsException(e);
		} catch (IOException e) {
			throw new SensorsException(e);
		} catch (IllegalStateException e) {
			throw new SensorsException(e);
		}
	}

	public void notifyUpdateError() {
		for(Handler callback : callbacks) {
			Message message = Message.obtain(callback, CommonData.MESSAGE_UPDATE_ERROR);
			callback.sendMessage(message);
		}
	}

	public Map<String, Integer> getStateCounts() {
		Map<String, Integer> stateCounts = new HashMap<String, Integer>();
		for(String stateName : states.keySet()) {
			stateCounts.put(stateName, status.getStateCount(states.get(stateName)));
		}
		return stateCounts;
	}

	public List<Measurement> getMeasurements() {
		return status.getMeasurements();
	}

	public void removeCallback(OverviewHandler overviewHandler) {
		callbacks.remove(overviewHandler);
	}
}
