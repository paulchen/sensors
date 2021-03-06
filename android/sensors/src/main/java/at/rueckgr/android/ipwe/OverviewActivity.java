package at.rueckgr.android.ipwe;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.ProgressDialog;
import android.content.ComponentName;
import android.content.Context;
import android.content.DialogInterface;
import android.content.DialogInterface.OnClickListener;
import android.content.Intent;
import android.content.ServiceConnection;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.os.Message;
import android.os.Messenger;
import android.os.RemoteException;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.view.WindowManager.BadTokenException;
import android.widget.ListView;
import android.widget.Toast;
import at.rueckgr.android.ipwe.data.Status;

public class OverviewActivity extends Activity implements ServiceConnection {
    private static final String TAG = "OverviewActivity";
    private SensorsApplication application;
    private static OverviewHandler overviewHandler;
    private IBinder serviceBinder;
    private Status lastStatus;
	private boolean serviceUp;
	private ProgressDialog progressDialog;
	private boolean showToasts;
	private Intent serviceIntent;
    
    private static class OverviewHandler extends Handler {
    	private OverviewActivity activity;

		public OverviewHandler(OverviewActivity activity) {
    		super();
    		this.activity = activity;
    	}

    	@Override
    	public void handleMessage(Message msg) {
    		switch(msg.what) {
    			case SensorsApplication.MESSAGE_UPDATE_SUCCESS:
    				activity.notifyUpdate((Status)msg.obj, true);
    				break;
    				
    			case SensorsApplication.MESSAGE_UPDATE_ERROR:
    				activity.notifyError();
    				break;
    			
    			case SensorsApplication.MESSAGE_UPDATE_START:
    				activity.notifyUpdateStart();
    				break;
    				
    			default:
    				/* will never happen */
    		}
    	}
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
		overviewHandler = new OverviewHandler(this);
        application = (SensorsApplication)getApplication();
        
        setContentView(R.layout.activity_overview);

        if(!application.isConfigured()) {
        	DialogInterface.OnClickListener dialogClickListener = new OnClickListener() {
				@Override
				public void onClick(DialogInterface dialog, int which) {
    				dialog.dismiss();
    				Intent intent = new Intent(OverviewActivity.this, SettingsActivity.class);
    				startActivity(intent);
				}
			};
			
    		new AlertDialog.Builder(OverviewActivity.this).setTitle(getString(R.string.welcome))
    			.setMessage(getString(R.string.dialog_first_run))
    			.setCancelable(false)
    			.setPositiveButton(android.R.string.ok, dialogClickListener)
    			.show();
    	}
        else {
        	notifyUpdateStart();
        }
        
    	serviceIntent = new Intent(this, PollService.class);
    	bindService(serviceIntent, this, Context.BIND_AUTO_CREATE);
    }

    @Override
	protected void onRestart() {
		super.onRestart();
		
		showToasts = true;
	}

	@Override
	protected void onStart() {
		super.onStart();
		
		showToasts = true;
	}

	@Override
	protected void onStop() {
		super.onStop();
		
		showToasts = false;
	}

	public void notifyUpdateStart() {
		if(progressDialog == null) {
	    	Log.d(TAG, "notifyUpdateStart");
	    	try {
	    		progressDialog = ProgressDialog.show(this, "", getString(R.string.status_updating), true);
	    	}
	    	catch (BadTokenException e) {
	    		/* ignore */
	    	}
		}
	}

	@Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.activity_overview, menu);
        return true;
    }
    
	public void notifyUpdate(Status status, boolean showToast) {
		if(showToast) {
			toast(getString(R.string.sensors_updated));
		}
		
		lastStatus = status;
		Log.d(TAG, "Notification received");

        StatusArrayAdapter statusArrayAdapter = new StatusArrayAdapter(this, R.layout.overview_list_item, status.getMeasurements(application.isSettingsHidden()));
	    ((ListView)findViewById(R.id.overviewList)).setAdapter(statusArrayAdapter);
		
		hideProgressDialog();
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

		new AlertDialog.Builder(this)
			.setMessage(R.string.dialog_are_you_sure)
			.setPositiveButton(android.R.string.yes, dialogClickListener)
		    .setNegativeButton(android.R.string.no, dialogClickListener)
		    .show();
	}
	
	private void shutdown() {
		try {
			new Messenger(serviceBinder).send(Message.obtain(null, SensorsApplication.MESSAGE_REMOVE_CLIENT));
		}
		catch (RemoteException e) {
			/* ignore */
		}
    	stopService(new Intent(this, PollService.class));
		finish();
	}
	
	@Override
	public void onDestroy() {
		super.onDestroy();
		
		unbindService(this);
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
			triggerUpdate();
			break;
		}
		
		return true;
	}

	private void triggerUpdate() {
		if(!serviceUp) {
			return;
		}
		
		try {
			new Messenger(serviceBinder).send(Message.obtain(null, SensorsApplication.MESSAGE_TRIGGER_UPDATE));
		}
		catch (RemoteException e) {
			toast("A problem occurred while initiating an update.");
		}
	}

	public void notifyError() {
		toast(getString(R.string.sensors_update_error));
		
		if(lastStatus != null) {
			notifyUpdate(lastStatus, false);
		}
		
		hideProgressDialog();
	}

	private void hideProgressDialog() {
		if(progressDialog != null) {
			progressDialog.dismiss();
		}
		
		progressDialog = null;
	}
	
	private void toast(String string) {
		if(showToasts) {
			Toast toast = Toast.makeText(this, string, Toast.LENGTH_SHORT);
			toast.show();
		}
	}

	@Override
	public void onServiceConnected(ComponentName name, IBinder service) {
		serviceBinder = service;
		try {
			Message message = Message.obtain(null, SensorsApplication.MESSAGE_ADD_CLIENT);
			message.replyTo = new Messenger(overviewHandler);
			(new Messenger(service)).send(message);
			serviceUp = true;
			
			startService(serviceIntent);
		}
		catch (RemoteException e) {
			initError();
		}
	}

	@Override
	public void onServiceDisconnected(ComponentName name) {
		serviceUp = false;
	}
	
	private void initError() {
    	DialogInterface.OnClickListener dialogClickListener = new OnClickListener() {
			@Override
			public void onClick(DialogInterface dialog, int which) {
				dialog.dismiss();
				shutdown();
			}
		};

		new AlertDialog.Builder(OverviewActivity.this).setTitle(getString(R.string.error))
			.setMessage(getString(R.string.error_init_close))
			.setCancelable(false)
			.setPositiveButton(getString(android.R.string.ok), dialogClickListener)
			.show();
	}
}
