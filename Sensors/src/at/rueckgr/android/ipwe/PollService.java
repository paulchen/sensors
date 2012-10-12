package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import android.app.AlarmManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Handler;
import android.os.IBinder;
import android.os.Message;
import android.os.Messenger;
import android.os.RemoteException;
import android.util.Log;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.Status;

public class PollService extends Service {
	private static final String TAG = "PollService";
	private Messenger incomingMessenger;
	private SensorsApplication application;
	private AlarmManager alarmManager;
	private PendingIntent pendingIntent;
	private static List<Messenger> clients;
	private ExecutorService threadPool;
	private BroadcastReceiver timerReceiver;
	
	static {
		clients = Collections.synchronizedList(new ArrayList<Messenger>());
	}
	
	public PollService() {
		incomingMessenger = new Messenger(new IncomingHandler(this));
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
				}
				catch (SensorsException e) {
					notifyUpdateError();
				}
				
				scheduleUpdate();
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
		
		update(true, false);
	
		return START_STICKY;
	}
	
	@Override
	public void onDestroy() {
		super.onDestroy();
		alarmManager.cancel(pendingIntent);
		unregisterReceiver(timerReceiver);
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
