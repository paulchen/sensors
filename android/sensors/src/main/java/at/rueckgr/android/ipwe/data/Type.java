package at.rueckgr.android.ipwe.data;

public class Type {
	private int id;
	private String name;
	private String format;
	private Integer min;
	private Integer max;
	private int decimals;
	private boolean hide;
	
	public Type(int id, String name, String format, Integer min, Integer max,
			int decimals, boolean hide) {
		this.id = id;
		this.name = name;
		this.format = format;
		this.min = min;
		this.max = max;
		this.decimals = decimals;
		this.hide = hide;
	}
	
	public int getId() {
		return id;
	}
	
	public void setId(int id) {
		this.id = id;
	}
	
	public String getName() {
		return name;
	}
	
	public void setName(String name) {
		this.name = name;
	}
	
	public String getFormat() {
		return format;
	}
	
	public void setFormat(String format) {
		this.format = format;
	}
	
	public Integer getMin() {
		return min;
	}
	
	public void setMin(Integer min) {
		this.min = min;
	}
	
	public Integer getMax() {
		return max;
	}
	
	public void setMax(Integer max) {
		this.max = max;
	}
	
	public int getDecimals() {
		return decimals;
	}
	
	public void setDecimals(int decimals) {
		this.decimals = decimals;
	}

	public boolean isHide() {
		return hide;
	}

	public void setHide(boolean hide) {
		this.hide = hide;
	}
}
