package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import android.app.AlarmManager;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.graphics.Color;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.os.Handler;
import android.os.IBinder;
import android.os.Message;
import android.os.Messenger;
import android.os.RemoteException;
import android.text.Spannable;
import android.text.SpannableString;
import android.text.style.ForegroundColorSpan;
import android.util.Log;
import android.util.Pair;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.Status;

public class PollService extends Service {
	private static final String TAG = "PollService";
	private Messenger incomingMessenger;
	private SensorsApplication application;
	private AlarmManager alarmManager;
	private PendingIntent pendingIntent;
	private static Set<Messenger> clients;
	private ExecutorService threadPool;
	private BroadcastReceiver timerReceiver;
	private ConnectionStateReceiver connectionStateReceiver;
	
	static {
		clients = Collections.synchronizedSet(new HashSet<Messenger>());
	}
	
	public PollService() {
		incomingMessenger = new Messenger(new IncomingHandler(this));
	}
	
    private class ConnectionStateReceiver extends BroadcastReceiver {
    	private static final String TAG = "ConnectionStateReceiver";    	
		private PollService service;
		
		public ConnectionStateReceiver(PollService service) {
			this.service = service;
		}
		
    	@Override
    	public void onReceive(Context context, Intent intent) {
    		Log.d(TAG, "receiver called");
    		
        	ConnectivityManager connectivityManager = (ConnectivityManager)context.getSystemService(Context.CONNECTIVITY_SERVICE);
        	 
        	NetworkInfo activeNetwork = connectivityManager.getActiveNetworkInfo();
        	if(activeNetwork != null && activeNetwork.isConnectedOrConnecting()) {
    			Log.d(TAG, "Forcing update");
				service.cancelPendingUpdate();
				service.update(true, false);
    		}
    	}
    }

	private static class IncomingHandler extends Handler {
		private PollService service;
		
		public IncomingHandler(PollService service) {
			this.service = service;
		}
		
		@Override
		public void handleMessage(Message msg) {
			switch(msg.what) {
				case SensorsApplication.MESSAGE_ADD_CLIENT:
					clients.add(msg.replyTo);
					break;
					
				case SensorsApplication.MESSAGE_REMOVE_CLIENT:
					clients.remove(msg.replyTo);
					break;
				
				case SensorsApplication.MESSAGE_TRIGGER_UPDATE:
					service.cancelPendingUpdate();
					service.update(true, false);
					break;
			}
		}
	}
	
	private class UpdateThread extends Thread {
		private boolean explicit;
		private boolean silent;

		public UpdateThread(boolean explicit, boolean silent) {
			super();
			this.explicit = explicit;
			this.silent = silent;
		}

		public void run() {
			application.readConfig(application);
			
			if(explicit || application.getSettingsRefresh()) {
				if(!silent) {
					notifyUpdateStart();
				}
			
				Log.e(TAG, "Updating...");
				Status status = new Status(application);
				try {
					status.update();
					notifyUpdate(status);
					updateNotification(status);
				}
				catch (SensorsException e) {
					notifyUpdateError();
				}
				
				scheduleUpdate();
			}
		}

		private void updateNotification(Status status) {
			Map<String, Integer> stateCounts = status.getStateCounts(application.isSettingsHidden());
			int total = 0;
			int ok = 0;
			for(String stateName : stateCounts.keySet()) {
				total += stateCounts.get(stateName);
				if(application.getState(stateName).isOk()) {
					ok += stateCounts.get(stateName);
				}
			}
			
			NotificationManager mNotificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
			if(ok != total) {
				if(application.isEnableNotifications()) {
					String statusDetails = "";
					List<at.rueckgr.android.ipwe.data.State> states = new ArrayList<at.rueckgr.android.ipwe.data.State>(application.getStates().values());
					Collections.sort(states);
					Map<at.rueckgr.android.ipwe.data.State, Pair<Integer, Integer>> spanPositions = new HashMap<at.rueckgr.android.ipwe.data.State, Pair<Integer, Integer>>();
					for(at.rueckgr.android.ipwe.data.State state : states) {
						statusDetails += String.format(getString(R.string.notification_details), state.getLetter(), stateCounts.get(state.getName()));
						if(stateCounts.get(state.getName()) > 0 || state.isOk()) {
							int pos1 = statusDetails.indexOf("[");
							int pos2 = statusDetails.indexOf("]")-1;
							spanPositions.put(state, new Pair<Integer, Integer>(pos1, pos2));
						}
						statusDetails = statusDetails.replaceAll("[\\[\\]]", "");
					}
					String statusText = String.format(getString(R.string.notification_text), total, statusDetails);
					int offset = statusText.indexOf("|");
					statusText = statusText.replaceAll("\\|", "");
					SpannableString statusSpannable = new SpannableString(statusText);
					for(at.rueckgr.android.ipwe.data.State state : spanPositions.keySet()) {
						Pair<Integer, Integer> pos = spanPositions.get(state);
						statusSpannable.setSpan(new ForegroundColorSpan(Color.parseColor(state.getColor())), pos.first+offset, pos.second+offset, Spannable.SPAN_INCLUSIVE_INCLUSIVE);
					}
					
					Notification.Builder notification = new Notification.Builder(getApplicationContext())
								.setContentTitle(getString(R.string.sensor_report))
								.setContentText(statusSpannable)
								.setSmallIcon(R.drawable.ic_launcher)
								.setContentIntent(PendingIntent.getActivity(getApplicationContext(), 0, new Intent(getApplicationContext(), OverviewActivity.class), 0))
								.setOngoing(true);
					if(application.isEnableNotificationLight()) {
						Log.d(TAG, String.valueOf(application.getNotificationLightColor()));
						notification.setLights(application.getNotificationLightColor(), 2000, 2000);
					}
					
					mNotificationManager.notify(SensorsApplication.NOTIFICATION_ID, notification.getNotification());
				}
				else {
					mNotificationManager.cancel(SensorsApplication.NOTIFICATION_ID);
				}
			}
			else {
				mNotificationManager.cancel(SensorsApplication.NOTIFICATION_ID);
			}
		}
	}
	
	private class TimerReceiver extends BroadcastReceiver {
		@Override
		public void onReceive(Context context, Intent intent) {
			Log.d(TAG, "Broadcast received");
			update(false, true);
		}
	}
	
	private void update(boolean explicit, boolean silent) {
		threadPool.execute(new UpdateThread(explicit, silent));
	}
	
	public void cancelPendingUpdate() {
		alarmManager.cancel(pendingIntent);
	}

	@Override
	public IBinder onBind(Intent arg0) {
		return incomingMessenger.getBinder();
	}

	@Override
	public int onStartCommand(Intent intent, int flags, int startId) {
		Log.d(TAG, "Service started");
		
		application = (SensorsApplication)getApplication();
		alarmManager = (AlarmManager)getSystemService(Context.ALARM_SERVICE);
		threadPool = Executors.newSingleThreadExecutor();
		
		timerReceiver = new TimerReceiver();
		registerReceiver(timerReceiver, new IntentFilter(SensorsApplication.TIMER_INTENT_NAME));
		Intent timerIntent = new Intent(SensorsApplication.TIMER_INTENT_NAME);
		pendingIntent = PendingIntent.getBroadcast(application, 0, timerIntent, 0);
		
    	connectionStateReceiver = new ConnectionStateReceiver(this);
    	IntentFilter intentFilter = new IntentFilter("android.net.conn.CONNECTIVITY_CHANGE");
    	registerReceiver(connectionStateReceiver, intentFilter);
    	
		return START_STICKY;
	}
	
	@Override
	public void onDestroy() {
		super.onDestroy();
		alarmManager.cancel(pendingIntent);
		unregisterReceiver(timerReceiver);
		unregisterReceiver(connectionStateReceiver);
	}

	private void scheduleUpdate() {
		Log.d(TAG, "Scheduling next update");
		alarmManager.set(AlarmManager.RTC_WAKEUP, System.currentTimeMillis()+application.getSettingsRefreshInterval()*1000, pendingIntent);		
	}
	
	public void notifyUpdate(Status status) {
		synchronized (clients) {
			for(Messenger messenger : clients) {
				try {
					messenger.send(Message.obtain(null, SensorsApplication.MESSAGE_UPDATE_SUCCESS, status));
				}
				catch (RemoteException e) {
					/* ignore */
				}
			}
		}
	}

	public void notifyUpdateError() {
		synchronized (clients) {
			for(Messenger messenger : clients) {
				try {
					messenger.send(Message.obtain(null, SensorsApplication.MESSAGE_UPDATE_ERROR));
				}
				catch (RemoteException e) {
					/* ignore */
				}
			}
		}
	}

	public void notifyUpdateStart() {
		synchronized (clients) {
			for(Messenger messenger : clients) {
				try {
					messenger.send(Message.obtain(null, SensorsApplication.MESSAGE_UPDATE_START));
				}
				catch (RemoteException e) {
					/* ignore */
				}
			}
		}
	}
}
