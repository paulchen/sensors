package at.rueckgr.android.ipwe;

import android.os.Bundle;
import android.app.Activity;
import android.view.Menu;
import at.rueckgr.android.ipwe.R;

public class OverviewActivity extends Activity {

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_overview);
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.activity_overview, menu);
        return true;
    }
}
