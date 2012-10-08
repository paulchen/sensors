package at.rueckgr.android.ipwe;

import java.util.Map;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.DialogInterface;
import android.content.DialogInterface.OnClickListener;
import android.content.Intent;
import android.graphics.Color;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.ListView;
import android.widget.Toast;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.State;

public class OverviewActivity extends Activity implements Notifyable {
    private static final String TAG = "OverviewActivity";
    private CommonData commonData;
    private OverviewActivity _this;
    private OverviewHandler overviewHandler;
    
    public OverviewActivity() {
        _this = this;
    }
    
	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        commonData = CommonData.getInstance();
        try {
			commonData.setContext(this);
		} catch (SensorsException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
        
        setContentView(R.layout.activity_overview);
        
        overviewHandler = new OverviewHandler(this);
        commonData.addCallback(overviewHandler);

        if(!commonData.isConfigured()) {
        	DialogInterface.OnClickListener dialogClickListener = new OnClickListener() {
				@Override
				public void onClick(DialogInterface dialog, int which) {
    				dialog.dismiss();
    				Intent intent = new Intent(_this, SettingsActivity.class);
    				startActivity(intent);
				}
			};
			
    		// TODO don't hardcode strings
    		new AlertDialog.Builder(_this).setTitle("Welcome")
    			.setMessage("This is the first time you run this app. You will have to configure it in order to use it.")
    			.setCancelable(false)
    			.setPositiveButton("Ok", dialogClickListener)
    			.show();
    	}
        
        if(commonData.pollServiceIntent == null) {
        	commonData.pollServiceIntent = new Intent(this, PollService.class);
        	startService(commonData.pollServiceIntent);
        }
        else {
        	notifyUpdate(false);
        }
    }

	@Override
	public void onPause() {
		super.onPause();
		
		commonData.removeCallback(overviewHandler);
	}
	
	@Override
	public void onResume() {
		super.onResume();
		
        commonData = CommonData.getInstance();
        try {
			commonData.setContext(this);
		} catch (SensorsException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}        
		commonData.addCallback(overviewHandler);
	}
	
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.activity_overview, menu);
        return true;
    }

    
    @Override
    public void notifyUpdate() {
    	notifyUpdate(true);
    }
    
	public void notifyUpdate(boolean showToast) {
		if(showToast) {
			// TODO don't hardcode string
			Toast toast = Toast.makeText(this, "Sensors updated", Toast.LENGTH_SHORT);
			toast.show();
		}
		
		Log.d(TAG, "Notification received");
		
		// TODO why is this necessary? why is update() called even if nothing has been updated yet?
		if(commonData.getStatus() == null || commonData.getStatus().getSensors() == null) {
			return;
		}
		
		Map<String, Integer> stateCounts = commonData.getStateCounts();
		int total = 0;
		int ok = 0;
		for(String stateName : stateCounts.keySet()) {
			total += stateCounts.get(stateName);
			if(commonData.getState(stateName).isOk()) {
				ok += stateCounts.get(stateName);
			}
		}
		
		NotificationManager mNotificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
		if(ok != total) {
			String statusText = "Sensors: " + total;
			for(String stateName : stateCounts.keySet()) {
				State state = commonData.getState(stateName);
				statusText += " - " + state.getLetter() + ": " + stateCounts.get(stateName);
			}
			// TODO don't hardcode strings here
			// TODO configurable
			Notification notification = new Notification.Builder(getApplicationContext())
						.setContentTitle("Sensor report")
						.setContentText(statusText)
						.setSmallIcon(R.drawable.ic_launcher)
						.setOngoing(true)
						.setLights(Color.argb(0, 255, 0, 255), 100, 200)
						.setContentIntent(PendingIntent.getActivity(this, 0, new Intent(this, OverviewActivity.class), 0))
						.getNotification();
			
			mNotificationManager.notify(CommonData.NOTIFICATION_ID, notification);
		}
		else {
			mNotificationManager.cancel(CommonData.NOTIFICATION_ID);
		}

        StatusArrayAdapter statusArrayAdapter = new StatusArrayAdapter(this, R.layout.overview_list_item, commonData.getMeasurements());
	    ((ListView)findViewById(R.id.overviewList)).setAdapter(statusArrayAdapter);
	}

	private void askShutdown() {
		OnClickListener dialogClickListener = new OnClickListener() {
		    @Override
		    public void onClick(DialogInterface dialog, int which) {
		        switch (which){
		        case DialogInterface.BUTTON_POSITIVE:
		            shutdown();
		            break;

		        case DialogInterface.BUTTON_NEGATIVE:
		            /* do nothing */
		            break;
		        }
		    }
		};

		// TODO don't hardcode strings here
		new AlertDialog.Builder(this)
			.setMessage("Are you sure?")
			.setPositiveButton("Yes", dialogClickListener)
		    .setNegativeButton("No", dialogClickListener)
		    .show();
	}
	
	private void shutdown() {
		stopService(commonData.pollServiceIntent);
		finish();
	}
	
	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		switch(item.getItemId()) {
		case R.id.menu_exit:
			askShutdown();
			break;
		
		case R.id.menu_settings:
			Intent intent = new Intent(this, SettingsActivity.class);
			startActivity(intent);
			break;
			
		case R.id.menu_update:
			commonData.pollService.triggerUpdate();
			break;
		}
		
		return true;
	}

	@Override
	public void notifyError() {
		// TODO don't hardcode string
		Toast toast = Toast.makeText(this, "Error while updating sensors.", Toast.LENGTH_SHORT);
		toast.show();
	}
}
