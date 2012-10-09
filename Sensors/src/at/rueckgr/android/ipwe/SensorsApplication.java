package at.rueckgr.android.ipwe;

import java.io.IOException;
import java.io.InputStream;
import java.net.URI;
import java.net.URISyntaxException;
import java.util.HashMap;
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
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.State;

public class SensorsApplication extends Application {
	public static final int NOTIFICATION_ID = 1;
	
	public static final int MESSAGE_UPDATE_SUCCESS = 0;
	public static final int MESSAGE_UPDATE_ERROR = 1;
	public static final int MESSAGE_ADD_CLIENT = 2;
	public static final int MESSAGE_REMOVE_CLIENT = 3;
	public static final int MESSAGE_TRIGGER_UPDATE = 4;

	private Map<String, State> states;

	private boolean configured;
	private String settingsURL;
	private boolean settingsRefresh;
	private int settingsRefreshInterval;
	private String settingsUsername;
	private String settingsPassword;
	private boolean settingsAuth;

	public SensorsApplication() {
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
	
	/*
	public static SensorsApplication getInstance() {
		if(commonData == null) {
			commonData = new SensorsApplication();
		}
		return commonData;
	} */
	
	public State getState(String name) {
		return states.get(name);
	}
	
	public void readConfig(Context context) {
		SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(context);
		configured = preferences.getBoolean("configured", false);
		settingsURL = preferences.getString("settings_url", "");
		settingsRefresh = preferences.getBoolean("settings_refresh", true);
		try {
			settingsRefreshInterval = Integer.parseInt(preferences.getString("settings_refresh_interval", "300"));
		}
		catch (NumberFormatException e) {
			settingsRefreshInterval = 300;
		}
		settingsUsername = preferences.getString("settings_username", "");
		settingsPassword = preferences.getString("settings_password", "");
		settingsAuth = preferences.getBoolean("settings_auth", false);
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

	public Map<String, State> getStates() {
		return states;
	}
}
