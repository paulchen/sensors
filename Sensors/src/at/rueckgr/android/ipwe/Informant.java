package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.List;

import android.os.Handler;
import android.os.Message;
import at.rueckgr.android.ipwe.data.Status;

public class Informant {
	private static Informant instance;
	private List<Handler> callbacks;
	
	private Informant() {
		callbacks = new ArrayList<Handler>();
	}
	
	public static Informant getInstance() {
		if(instance == null) {
			instance = new Informant();
		}
		return instance;
	}

	public void notifyUpdate(Status status) {
		for(Handler callback : callbacks) {
			// TODO magic number 0?
			Message message = Message.obtain(callback, 0, status);
			callback.sendMessage(message);
		}
	}
	
	public void addCallback(Handler callback) {
		callbacks.add(callback);
	}
}