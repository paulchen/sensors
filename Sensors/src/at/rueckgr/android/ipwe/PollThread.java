package at.rueckgr.android.ipwe;

import android.os.Looper;
import android.util.Log;
import at.rueckgr.android.ipwe.data.Status;

public class PollThread extends Thread {
	private static final String TAG = "PollThread";
	
	private Status status;
	
	public PollThread(Status status) {
		this.status = status;
	}
	
	@Override
	public void run() {
		Looper.prepare();
		
		// TODO
		Log.d(TAG, "Thread started");
		
		Informant informant = Informant.getInstance();
		for(;;) {
			try {
				Log.e(TAG, "Updating...");
				status.update();
				informant.notifyUpdate(status);
				// TODO configurable interval
				Thread.sleep(300000);
			}
			catch (InterruptedException e) {
				/* do nothing */
			}
		}
	}
}
