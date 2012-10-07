package at.rueckgr.android.ipwe;

import android.os.Looper;
import android.util.Log;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.Status;

public class PollThread extends Thread {
	private static final String TAG = "PollThread";
	
	private Status status;
	private CommonData commonData;
	
	public PollThread(Status status) {
		this.status = status;
		commonData = CommonData.getInstance();
	}
	
	@Override
	public void run() {
		Looper.prepare();
		
		Log.d(TAG, "Thread started");
		
		for(;;) {
			try {
				if(commonData.getSettingsRefresh()) {
					Log.e(TAG, "Updating...");
					status.update();
					commonData.notifyUpdate(status);
				}
				Thread.sleep(commonData.getSettingsRefreshInterval() * 1000);
			}
			catch (SensorsException e) {
				e.printStackTrace();
				commonData.notifyUpdateError();
			}
			catch (InterruptedException e) {
				/* do nothing */
			}
		}
	}
}
