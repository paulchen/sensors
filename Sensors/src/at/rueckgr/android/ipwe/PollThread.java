package at.rueckgr.android.ipwe;

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
		// TODO
		Log.d(TAG, "Thread started");
		
		for(;;) {
			try {
				Log.e(TAG, "Updating...");
				status.update();
				// TODO configurable interval
				Thread.sleep(300000);
			}
			catch (InterruptedException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
		}
	}
}
