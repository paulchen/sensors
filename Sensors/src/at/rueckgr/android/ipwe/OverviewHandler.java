package at.rueckgr.android.ipwe;

import android.os.Handler;
import android.os.Message;
import at.rueckgr.android.ipwe.data.Status;

public class OverviewHandler extends Handler {
	private Notifyable callback;
	private CommonData commonData;
	
	public OverviewHandler(Notifyable callback) {
		super();
		this.callback = callback;
		commonData = CommonData.getInstance();
	}

	@Override
	public void handleMessage(Message msg) {
		commonData.setStatus((Status)msg.obj);
		callback.notifyUpdate();
	}
}
