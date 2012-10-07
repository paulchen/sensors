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
		switch(msg.what) {
			case CommonData.MESSAGE_UPDATE_SUCCESS:
				commonData.setStatus((Status)msg.obj);
				callback.notifyUpdate();
				break;
				
			case CommonData.MESSAGE_UPDATE_ERROR:
				callback.notifyError();
				break;
			
			default:
				/* will never happen */
		}
	}
}
