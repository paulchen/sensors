package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import android.app.Service;
import android.content.Intent;
import android.os.Handler;
import android.os.IBinder;
import android.os.Message;
import android.os.Messenger;
import android.os.RemoteException;
import android.util.Log;
import at.rueckgr.android.ipwe.data.Status;

public class PollService extends Service {
	private static final String TAG = "PollService";
	// TODO really static?!
	private static PollThread pollThread;
	private Messenger incomingMessenger;
	private static List<Messenger> clients;
	
	static {
		clients = Collections.synchronizedList(new ArrayList<Messenger>());
	}
	
	public PollService() {
		incomingMessenger = new Messenger(new IncomingHandler());
	}
	
	private static class IncomingHandler extends Handler {
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
					pollThread.interrupt();
					break;
			}
		}
	}
	
	@Override
	public IBinder onBind(Intent arg0) {
		return incomingMessenger.getBinder();
	}

	@Override
	public int onStartCommand(Intent intent, int flags, int startId) {
		Log.d(TAG, "Service started");
		
		pollThread = new PollThread(this, (SensorsApplication)getApplication());
		pollThread.start();
		
		return START_STICKY;
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
}
