package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.List;

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
import at.rueckgr.android.ipwe.data.Measurement;
import at.rueckgr.android.ipwe.data.Sensor;
import at.rueckgr.android.ipwe.data.Value;

public class OverviewActivity extends Activity implements InformantCallback {
    private static final String TAG = "OverviewActivity";
    private CommonData commonData;
    private OverviewActivity _this;
    
    public OverviewActivity() {
        commonData = CommonData.getInstance();
        commonData.setContext(this);
        _this = this;
    }
    
	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_overview);
        
        Informant.getInstance().addCallback(new OverviewHandler(this));

        if(!commonData.isConfigured()) {
    		AlertDialog alertDialog = new AlertDialog.Builder(_this).create();
    		// TODO don't hardcode strings
    		alertDialog.setTitle("Welcome");
    		alertDialog.setMessage("This is the first time you run this app. You will have to configure it in order to use it.");
    		alertDialog.setButton("Ok", new OnClickListener() {
    			@Override
    			public void onClick(DialogInterface dialog, int which) {
    				dialog.dismiss();
    				Intent intent = new Intent(_this, SettingsActivity.class);
    				startActivity(intent);
    			}
    		});
    		alertDialog.show();
    	}
        
        if(commonData.pollServiceIntent == null) {
        	commonData.pollServiceIntent = new Intent(this, PollService.class);
        	startService(commonData.pollServiceIntent);
        }
        else {
        	update(false);
        }
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.activity_overview, menu);
        return true;
    }

    
    @Override
    public void update() {
    	update(true);
    }
    
	public void update(boolean showToast) {
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
		
		// TODO generalize?
		int warning = 0;
		int critical = 0;
		int ok = 0;
		List<Measurement> measurements = new ArrayList<Measurement>();
		for(Sensor sensor : commonData.getStatus().getSensors()) {
			for(Value value : sensor.getValues()) {
				measurements.addAll(value.getMeasurements());
				for(Measurement measurement : value.getMeasurements()) {
					if(measurement.getState().getName().equals("warning")) {
						warning++;
					}
					else if(measurement.getState().getName().equals("critical")) {
						critical++;
					}
					else {
						ok++;
					}
				}
			}
		}
		int total = ok + warning + critical;
		
		NotificationManager mNotificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
		if(warning + critical > 0) {			
			// TODO don't hardcode strings here
			// TODO configurable
			Notification notification = new Notification.Builder(getApplicationContext())
						.setContentTitle("Sensor report")
						.setContentText("Sensors: " + total + " - O: " + ok + " - W: " + warning + " - C: " + critical)
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

        StatusArrayAdapter statusArrayAdapter = new StatusArrayAdapter(this, R.layout.overview_list_item, measurements);
	    ((ListView)findViewById(R.id.overviewList)).setAdapter(statusArrayAdapter);
	}

	private void askShutdown() {
		DialogInterface.OnClickListener dialogClickListener = new DialogInterface.OnClickListener() {
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

		AlertDialog.Builder builder = new AlertDialog.Builder(this);
		// TODO don't hardcode strings here
		builder.setMessage("Are you sure?").setPositiveButton("Yes", dialogClickListener)
		    .setNegativeButton("No", dialogClickListener).show();
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
}
