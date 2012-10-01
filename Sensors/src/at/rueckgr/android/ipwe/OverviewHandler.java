package at.rueckgr.android.ipwe;

import android.os.Handler;
import android.os.Message;
import at.rueckgr.android.ipwe.data.Status;

public class OverviewHandler extends Handler {
	private InformantCallback callback;
	
	public OverviewHandler(InformantCallback callback) {
		super();
		this.callback = callback;
	}

	@Override
	public void handleMessage(Message msg) {
		// TODO refine
		callback.notify(new Status());
	}
}
