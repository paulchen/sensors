package at.rueckgr.android.ipwe.data;

public class SensorsException extends Exception {
	private static final long serialVersionUID = 8589691299691347494L;

	public SensorsException() {
		/* do nothing */
	}

	public SensorsException(String detailMessage) {
		super(detailMessage);
	}

	public SensorsException(Throwable throwable) {
		super(throwable);
	}

	public SensorsException(String detailMessage, Throwable throwable) {
		super(detailMessage, throwable);
	}
}
