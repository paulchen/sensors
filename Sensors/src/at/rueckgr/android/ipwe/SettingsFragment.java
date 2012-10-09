package at.rueckgr.android.ipwe;


import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.content.ServiceConnection;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.content.SharedPreferences.OnSharedPreferenceChangeListener;
import android.os.Bundle;
import android.os.IBinder;
import android.os.Message;
import android.os.Messenger;
import android.os.RemoteException;
import android.preference.PreferenceFragment;
import android.widget.Toast;

public class SettingsFragment extends PreferenceFragment implements OnSharedPreferenceChangeListener {
	private SharedPreferences preferences;
	
	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		addPreferencesFromResource(R.xml.preferences);
		
		preferences = getPreferenceScreen().getSharedPreferences();
		preferences.registerOnSharedPreferenceChangeListener(this);
	}

	@Override
	public void onPause() {
		super.onPause();
		
		preferences.unregisterOnSharedPreferenceChangeListener(this);
		
		Editor editor = preferences.edit();
		editor.putBoolean("configured", true);
		editor.commit();
		
		/* trigger an update */
		Intent intent = new Intent(getActivity(), PollService.class);
		ServiceConnection serviceConnection = new ServiceConnection() {
			@Override
			public void onServiceDisconnected(ComponentName name) {
				/* do nothing */
			}
			
			@Override
			public void onServiceConnected(ComponentName name, IBinder service) {
				try {
					new Messenger(service).send(Message.obtain(null, SensorsApplication.MESSAGE_TRIGGER_UPDATE));
				}
				catch (RemoteException e) {
					/* ignore */
				}
				getActivity().unbindService(this);
			}
		};
		getActivity().bindService(intent, serviceConnection , Context.BIND_AUTO_CREATE);
	}
	
	@Override
	public void onResume() {
		super.onResume();
		
		preferences.registerOnSharedPreferenceChangeListener(this);
	}
	
	@Override
	public void onSharedPreferenceChanged(SharedPreferences sharedPreferences,
			String key) {
		if(key.equals("settings_refresh_interval")) {
			// TODO don't hardcode 300 here
			String originalInput = preferences.getString("settings_refresh_interval", "300");
			String input = originalInput.trim();
			while(input.length() > 0 && input.charAt(0) == '0') {
				input = input.substring(1);
			}
			if(input.equals("")) {
				// TODO don't hardcode string
				Toast.makeText(getPreferenceScreen().getContext(), "The value \"" + originalInput + "\" is invalid and will be ignored.", Toast.LENGTH_LONG).show();
			}
		}
	}
}
