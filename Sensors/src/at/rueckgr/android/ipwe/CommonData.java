package at.rueckgr.android.ipwe;

import java.io.IOException;
import java.io.InputStream;
import java.net.URI;
import java.net.URISyntaxException;
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

import android.app.Application;
import android.content.Context;
import android.content.SharedPreferences;
import android.preference.PreferenceManager;
import at.rueckgr.android.ipwe.data.Measurement;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.State;
import at.rueckgr.android.ipwe.data.Status;

// TODO rename to Common?
public class CommonData extends Application {
	public static final int NOTIFICATION_ID = 1;
	
	public static final int MESSAGE_UPDATE_SUCCESS = 0;
	public static final int MESSAGE_UPDATE_ERROR = 1;
	public static final int MESSAGE_ADD_CLIENT = 2;
	public static final int MESSAGE_REMOVE_CLIENT = 3;
	public static final int MESSAGE_TRIGGER_UPDATE = 4;

	private static final String TAG = "CommonData";
	
//	public Intent pollServiceIntent;
//	public PollService pollService;
	
	private static CommonData commonData;
	private Map<String, State> states;

//	private Status status;

//	private OverviewActivity context;

//	private SharedPreferences preferences;

//	private List<Handler> callbacks;

	private boolean configured;
	private String settingsURL;
	private boolean settingsRefresh;
	private int settingsRefreshInterval;
	private String settingsUsername;
	private String settingsPassword;
	private boolean settingsAuth;

	public CommonData() {
		initStates();
	}
	
	@Override
	public void onCreate() {
		super.onCreate();
		
		initStates();		
		readConfig(this);
	}

	private void initStates() {
		if(states == null) {
			states = new HashMap<String, State>();
			
			states.put("ok", new State("ok", "#00cc33", true));
			states.put("warning", new State("warning", "#00cc33", false));
			states.put("critical", new State("critical", "#00cc33", false));
			states.put("unknown", new State("unknown", "#00cc33", false));
		}
	}
	
	public static CommonData getInstance() {
		if(commonData == null) {
			commonData = new CommonData();
		}
		return commonData;
	}
	
	/*
	public boolean isServiceRunning(Class<? extends Service> serviceClass) {
		final ActivityManager activityManager = (ActivityManager)context.getSystemService(Context.ACTIVITY_SERVICE);
		final List<RunningServiceInfo> services = activityManager.getRunningServices(Integer.MAX_VALUE);
		
		for (RunningServiceInfo runningServiceInfo : services) {
			if (runningServiceInfo.service.getClassName().equals(serviceClass.getName())){
				return true;
			}
		}
		return false;
	}
	*/

	public State getState(String name) {
		return states.get(name);
	}
	
	/*
	public Status getStatus() {
		return status;
	} */

	/*
	public void setStatus(Status status) {
		this.status = status;
	}
	*/

	/*
	public void setContext(OverviewActivity context) throws SensorsException {
		this.context = context;
		
		readConfig();
	}
*/
	public void readConfig(Context context) {
		configured = getPreferences(context).getBoolean("configured", false);
		settingsURL = getPreferences(context).getString("settings_url", "");
		settingsRefresh = getPreferences(context).getBoolean("settings_refresh", false);
		try {
			settingsRefreshInterval = Integer.parseInt(getPreferences(context).getString("settings_refresh_interval", "300"));
		}
		catch (NumberFormatException e) {
			settingsRefreshInterval = 300;
		}
		settingsUsername = getPreferences(context).getString("settings_username", "");
		settingsPassword = getPreferences(context).getString("settings_password", "");
		settingsAuth = getPreferences(context).getBoolean("settings_auth", false);
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
	
	private SharedPreferences getPreferences(Context context) {
		/* if(preferences == null) {
			if(context != null) {
				preferences = PreferenceManager.getDefaultSharedPreferences(context);
			}
			else {
				// TODO epic problem
				return null;
			}
		}*/
		return PreferenceManager.getDefaultSharedPreferences(context);
	}
	
	
	/*
	public void notifyUpdate(Status status) {
		for(Handler callback : callbacks) {
			Message message = Message.obtain(callback, CommonData.MESSAGE_UPDATE_SUCCESS, status);
			callback.sendMessage(message);
		}
	}
	
	public void addCallback(Handler callback) {
		callbacks.add(callback);
	}
	*/

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

	/*
	public void notifyUpdateError() {
		for(Handler callback : callbacks) {
			Message message = Message.obtain(callback, CommonData.MESSAGE_UPDATE_ERROR);
			callback.sendMessage(message);
		}
	}
	*/

	public Map<String, Integer> getStateCounts(Status status) {
		Map<String, Integer> stateCounts = new HashMap<String, Integer>();
		for(String stateName : states.keySet()) {
			stateCounts.put(stateName, status.getStateCount(states.get(stateName)));
		}
		return stateCounts;
	}

	// TODO move to status
	public List<Measurement> getMeasurements(Status status) {
		return status.getMeasurements();
	}

	/*
	public void removeCallback(OverviewHandler overviewHandler) {
		callbacks.remove(overviewHandler);
	}
	*/
}
