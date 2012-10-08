package at.rueckgr.android.ipwe;

import android.os.Looper;
import android.util.Log;
import at.rueckgr.android.ipwe.data.SensorsException;
import at.rueckgr.android.ipwe.data.Status;

public class PollThread extends Thread {
	private static final String TAG = "PollThread";
	
//	private Status status;
	private CommonData commonData;
	private PollService pollService;
	
	public PollThread(PollService pollService) {
		/*
		this.status = status;
		*/
		commonData = CommonData.getInstance();
		this.pollService = pollService;
	}
	
	@Override
	public void run() {
		Looper.prepare();
		
		Log.d(TAG, "Thread started");
		
		for(;;) {
			try {
				commonData.readConfig(pollService);
				if(CommonData.getInstance().getSettingsRefresh()) {
					Log.e(TAG, "Updating...");
					Status status = new Status();
					status.update();
					pollService.notifyUpdate(status);
				}
				Thread.sleep(commonData.getSettingsRefreshInterval() * 1000);
			}
			catch (SensorsException e) {
				e.printStackTrace();
				pollService.notifyUpdateError();
			}
			catch (InterruptedException e) {
				/* do nothing */
			}
		}
	}
}
