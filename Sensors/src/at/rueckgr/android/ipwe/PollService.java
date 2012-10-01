package at.rueckgr.android.ipwe;

import android.app.Service;
import android.content.Intent;
import android.os.IBinder;
import android.util.Log;
import at.rueckgr.android.ipwe.data.Status;

public class PollService extends Service {
	private static final String TAG = "PollService";
	
	@Override
	public IBinder onBind(Intent arg0) {
		// TODO Auto-generated method stub
		return null;
	}

	@Override
	public int onStartCommand(Intent intent, int flags, int startId) {
		Log.d(TAG, "Service started");
		
		// TODO hrm, put somewhere else?
		Status status = new Status();
		
		PollThread pollThread = new PollThread(status);
		pollThread.start();
		
		return START_STICKY;
	}
}