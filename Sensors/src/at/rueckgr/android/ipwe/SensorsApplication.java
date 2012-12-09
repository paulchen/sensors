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
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

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
	public static final int MESSAGE_UPDATE_START = 5;

	public static final String TIMER_INTENT_NAME = "at.rueckgr.android.ipwe.ScheduledUpdate";
	
	private Map<String, State> states;

	private boolean configured;
	private String settingsURL;
	private boolean settingsRefresh;
	private int settingsRefreshInterval;
	private String settingsUsername;
	private String settingsPassword;
	private boolean settingsAuth;

	private boolean enableNotifications;
	private boolean enableNotificationLight;
	private int notificationLightColor;

	public SensorsApplication() {
	}
	
	@Override
	public void onCreate() {
		super.onCreate();
		
		readConfig(this);
	}

	public void initStates(Node parentNode) {
		states = new HashMap<String, State>();
		
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("state")) {
				NamedNodeMap attributes = node.getAttributes();
				if(attributes.getNamedItem("name") != null && attributes.getNamedItem("pos") != null && attributes.getNamedItem("color") != null && attributes.getNamedItem("ok") != null) {
					try {
						String name = attributes.getNamedItem("name").getTextContent();
						int pos = Integer.parseInt(attributes.getNamedItem("pos").getTextContent());
						boolean ok = (Integer.parseInt(attributes.getNamedItem("pos").getTextContent()) == 1);
						String color = attributes.getNamedItem("color").getTextContent();
						
						states.put(name, new State(name, color, ok, pos));
					}
					catch (NumberFormatException e) {
						/* do nothing, just ignore that error */
					}
				}
			}
		}
	}
	
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
		
		enableNotifications = preferences.getBoolean("settings_notifications", true);
		enableNotificationLight = preferences.getBoolean("settings_notification_light", true);
		notificationLightColor = preferences.getInt("settings_led_color", 0xff00ff);
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
	
	public boolean isEnableNotifications() {
		return enableNotifications;
	}

	public boolean isEnableNotificationLight() {
		return enableNotificationLight;
	}

	public int getNotificationLightColor() {
		return notificationLightColor;
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
