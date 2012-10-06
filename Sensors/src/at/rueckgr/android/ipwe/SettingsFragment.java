package at.rueckgr.android.ipwe;


import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.content.SharedPreferences.OnSharedPreferenceChangeListener;
import android.os.Bundle;
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
		
		CommonData.getInstance().pollService.triggerUpdate();
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
