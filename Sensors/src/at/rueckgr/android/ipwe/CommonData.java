package at.rueckgr.android.ipwe;

import android.content.Intent;

public class CommonData {
	public Intent pollServiceIntent;
	public PollService pollService;
	
	private static CommonData commonData;
	
	private CommonData() {
		// TODO Auto-generated constructor stub
	}

	public static CommonData getInstance() {
		if(commonData == null) {
			commonData = new CommonData();
		}
		return commonData;
	}
}
