package at.rueckgr.android.ipwe.data;

public final class State {
	private final String name;
	private final String color;
	
	public State(String name, String color) {
		super();
		this.name = name;
		this.color = color;
	}

	public String getName() {
		return name;
	}

	public String getColor() {
		return color;
	}
	
	@Override
	public String toString() {
		return name;
	}
}
