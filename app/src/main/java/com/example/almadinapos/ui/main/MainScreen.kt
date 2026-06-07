package com.example.almadinapos.ui.main

import android.view.ViewGroup
import android.webkit.WebChromeClient
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.BackHandler
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.painterResource
import com.example.almadinapos.R
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.navigation3.runtime.NavKey

@Composable
fun MainScreen(
  onItemClick: (NavKey) -> Unit,
  modifier: Modifier = Modifier
) {
  var webView: WebView? by remember { mutableStateOf(null) }
  var isLoading by remember { mutableStateOf(true) }

  // Intercept physical/gesture back button on Android to navigate back inside the WebView
  BackHandler(enabled = webView?.canGoBack() == true) {
    webView?.goBack()
  }

  Box(
    modifier = Modifier
      .fillMaxSize()
      .systemBarsPadding() // Inset WebView to prevent overlapping notch & navigation bar
      .background(Color(0xFF020617)) // Slate-950 background
  ) {
    AndroidView(
      modifier = Modifier.fillMaxSize(),
      factory = { context ->
        WebView(context).apply {
          webView = this
          layoutParams = ViewGroup.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.MATCH_PARENT
          )
          webViewClient = object : WebViewClient() {
            override fun onPageFinished(view: WebView?, url: String?) {
              super.onPageFinished(view, url)
              isLoading = false
            }
          }
          webChromeClient = WebChromeClient()
          settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            loadWithOverviewMode = true
            useWideViewPort = true
            cacheMode = android.webkit.WebSettings.LOAD_DEFAULT
            allowContentAccess = true
            allowFileAccess = true
          }
          loadUrl("https://restaurant-pos-e5vb.onrender.com")
        }
      }
    )

    if (isLoading) {
      Box(
        modifier = Modifier
          .fillMaxSize()
          .background(
            Brush.verticalGradient(
              colors = listOf(
                Color(0xFF020617), // Dark Slate
                Color(0xFF061510), // Deep Forest Green Tint
                Color(0xFF0D0B07)  // Deep Orange/Amber Tint
              )
            )
          ),
        contentAlignment = Alignment.Center
      ) {
        Column(
          horizontalAlignment = Alignment.CenterHorizontally,
          verticalArrangement = Arrangement.Center
        ) {
          // Premium Logo with Green & Orange mixed gradient border
          Box(
            modifier = Modifier
              .size(110.dp)
              .clip(RoundedCornerShape(28.dp))
              .background(
                Brush.linearGradient(
                  colors = listOf(
                    Color(0xFF22C55E), // Green
                    Color(0xFFF97316)  // Orange
                  )
                )
              )
              .padding(3.dp)
              .clip(RoundedCornerShape(25.dp))
              .background(Color(0xFF020617)),
            contentAlignment = Alignment.Center
          ) {
            Image(
              painter = painterResource(id = R.drawable.logo),
              contentDescription = "Bello Smash Logo",
              modifier = Modifier
                .fillMaxSize()
                .padding(10.dp)
            )
          }

          Spacer(modifier = Modifier.height(28.dp))

          Text(
            text = "Bello Smash Burger",
            style = TextStyle(
              fontSize = 24.sp,
              fontWeight = FontWeight.Black,
              color = Color.White,
              letterSpacing = 0.5.sp
            )
          )

          Spacer(modifier = Modifier.height(6.dp))

          Text(
            text = "منظومة alnihowm",
            style = TextStyle(
              fontSize = 14.sp,
              fontWeight = FontWeight.Bold,
              color = Color(0xFFF97316),
              letterSpacing = 0.5.sp
            )
          )

          Spacer(modifier = Modifier.height(48.dp))

          CircularProgressIndicator(
            color = Color(0xFF22C55E), // Premium Green spinner
            strokeWidth = 3.5.dp,
            modifier = Modifier.size(38.dp)
          )
        }
      }
    }
  }
}
