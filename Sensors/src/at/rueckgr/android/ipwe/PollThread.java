package at.rueckgr.android.ipwe;

import android.os.Looper;
import android.util.Log;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.Status;

public class PollThread extends Thread {
	private static final String TAG = "PollThread";
	
	private SensorsApplication application;
	private PollService pollService;
	
	public PollThread(PollService pollService, SensorsApplication application) {
		this.pollService = pollService;
		this.application = application;
	}
	
	@Override
	public void run() {
		Looper.prepare();
		
		Log.d(TAG, "Thread started");
		
		boolean loaded = false;
		boolean hideProgressDialog = false;
		for(;;) {
			try {
				if(!hideProgressDialog) {
					pollService.notifyUpdateStart();
				}
				application.readConfig(pollService);
				if(!loaded || application.getSettingsRefresh()) {
					Log.e(TAG, "Updating...");
					Status status = new Status(application);
					status.update();
					pollService.notifyUpdate(status);
					
					loaded = true;
				}
			}
			catch (SensorsException e) {
				e.printStackTrace();
				pollService.notifyUpdateError();
			}
			try {
				hideProgressDialog = false;
				Thread.sleep(application.getSettingsRefreshInterval() * 1000);
				hideProgressDialog = true;
			}
			catch (InterruptedException e) {
				/* do nothing */
			}
		}
	}
}
